<?php
/**
 * @filesource modules/borrow/models/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Borrow\Index;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=borrow
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลรายการที่เลือก
     * ถ้า $id = 0 หมายถึงรายการใหม่
     * คืนค่าข้อมูล object ไม่พบคืนค่า null
     *
     * @param int   $id
     * @param array $login
     *
     * @return object|null
     */
    public static function get($id, $login)
    {
        if (empty($id)) {
            // ใหม่
            return (object) [
                'id' => 0,
                'borrower' => $login['name'],
                'borrower_id' => $login['id'],
                'borrow_no' => '',
                'transaction_date' => date('Y-m-d'),
                'borrow_date' => date('Y-m-d'),
                'return_date' => null,
                'borrower_emp_id' => null,
                'borrower_dept_id' => null,
                'borrower_fname' => null,
                'borrower_lname' => null,
                'borrower_phone' => null,
                'borrower_remark' => null
            ];
        } else {
            // แก้ไข อ่านรายการที่เลือก
            return static::createQuery()
                ->from('borrow B')
                ->where(['B.id', $id])
                ->notExists('borrow_items', [['borrow_id', $id], ['status', '>', 0]])
                ->first('B.*');
        }
    }

    /**
     * อ่านรายการพัสดุในใบยืม
     * ถ้าไมมีคืนค่ารายการว่าง 1 รายการ
     *
     * @param int $borrow_id
     *
     * @return array
     */
    public static function items($borrow_id)
    {
        if ($borrow_id > 0) {
            // แก้ไข
            $result = static::createQuery()
                ->select('S.borrow_id id', 'S.num_requests quantity', 'S.product_no', 'S.topic', 'S.unit', 'I.stock', 'V.count_stock')
                ->from('borrow_items S')
                ->join('inventory_items I', 'LEFT', ['I.product_no', 'S.product_no'])
                ->join('inventory V', 'LEFT', ['V.id', 'I.inventory_id'])
                ->where(['S.borrow_id', $borrow_id])
                ->order('S.id')
                ->toArray()
                ->execute();
        }
        if (empty($result)) {
            // ถ้าไม่มีผลลัพท์ คืนค่ารายการเปล่าๆ 1 รายการ
            $result = [
                0 => [
                    'id' => 0,
                    'quantity' => 0,
                    'product_no' => '',
                    'topic' => '',
                    'unit' => '',
                    'stock' => 0
                ]
            ];
        }
        return $result;

    }

    /**
     * บันทึกข้อมูลที่ส่งมาจากฟอร์ม ยืม (index.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, สมาชิก
        if ($request->initSession() && $request->isSafe()) {
            if ($login = Login::isMember()) {
                try {
                    $order = [
                        'borrower_id' => $login['id'],
                        'borrow_no' => $request->post('borrow_no')->topic(),
                        'transaction_date' => $request->post('transaction_date')->date(),
                        'borrow_date' => $request->post('borrow_date')->date(),
                        'return_date' => $request->post('return_date')->date(),
                        'borrower_emp_id' => $request->post('borrower_emp_id')->topic(),
                        'borrower_dept_id' => strtoupper($request->post('borrower_dept_id')->topic()),
                        'borrower_fname' => $request->post('borrower_fname')->topic(),
                        'borrower_lname' => $request->post('borrower_lname')->topic(),
                        'borrower_phone' => $request->post('borrower_phone')->topic(),
                        'borrower_remark' => $request->post('borrower_remark')->topic(),
                    ];
                    // ตรวจสอบรายการที่เลือก
                    $borrow = self::get($request->post('borrow_id')->toInt(), $login);
                    if ($borrow) {
                        // ชื่อตาราง
                        $table_borrow = $this->getTableName('borrow');
                        $table_borrow_items = $this->getTableName('borrow_items');
                        $table_inventory = $this->getTableName('inventory');
                        $table_inventory_items = $this->getTableName('inventory_items');
                        // Database
                        $db = $this->db();
                        // พัสดุที่เลือก
                        $datas = [
                            'quantity' => $request->post('quantity', [])->toInt(),
                            'topic' => $request->post('topic', [])->topic(),
                            'product_no' => $request->post('product_no', [])->topic(),
                            'unit' => $request->post('unit', [])->topic()
                        ];
                        $items = [];
                        $items_stock = [];
                        /** ทำรายการแล้วอนุมัติและตัดสต๊อค */
                        foreach ($datas['quantity'] as $key => $value) {
                            if ($value > 0 && $datas['product_no'][$key] != '') {
                                $items[$datas['product_no'][$key]] = [
                                    'num_requests' => $value,
                                    'topic' => $datas['topic'][$key],
                                    'product_no' => $datas['product_no'][$key],
                                    'unit' => $datas['unit'][$key],
                                    'status' => 2, // 0 ทำรายการ, 2 อนุม้ติ
                                    'amount' => $value // จำนวนส่งมอบ
                                ];

                                $items_stock[$datas['product_no'][$key]] = [
                                    'product_no' => $datas['product_no'][$key],
                                    'stock' => $value // จำนวนที่จะตัดสต็อค
                                ];

                                // ดึงข้อมูลสต๊อคปัจจุบัน
                                $stock = $this->db()->customQuery("select V.stock, V.unit, I.topic
                                                                    from {$table_inventory_items} V
                                                                    INNER JOIN {$table_inventory} I ON I.id = V.inventory_id
                                                                    where V.product_no = '{$datas['product_no'][$key]}'
                                                                    limit 1");

                                // ตรวจสอบสต๊อค
                                if ($value > $stock[0]->stock) {
                                    // สต๊อคไม่เพียงพอ
                                    $ret['alert'] = Language::replace('There is not enough :name (remaining :stock :unit)', [':name' => $stock[0]->topic, ':stock' => $stock[0]->stock, ':unit' => $stock[0]->unit]);
                                }
                            }
                        }
                        if (empty($items)) {
                            // ไม่ได้เลือก พัสดุ
                            $ret['ret_inventory'] = 'Please fill in';
                        }
                        if (empty($ret)) {
                            // ใหม่ หรือไม่ได้กรอก borrow_no มา
                            if ($borrow->id == 0 || $order['borrow_no'] == '') {
                                // สร้างเลข running number
                                $order['borrow_no'] = \Index\Number\Model::get($borrow->id, 'borrow_no', $table_borrow, 'borrow_no', self::$cfg->borrow_prefix);
                            } else {
                                // ตรวจสอบ borrow_no ซ้ำ
                                $search = $this->db()->first($table_borrow, [
                                    ['borrow_no', $order['borrow_no']]
                                ]);
                                if ($search !== false && $borrow->id != $search->id) {
                                    $ret['ret_borrow_no'] = Language::replace('This :name already exist', [':name' => Language::get('Order No.')]);
                                }
                            }
                            if (empty($ret)) {
                                if ($borrow->id > 0) {
                                    // แก้ไข
                                    $db->update($table_borrow, $borrow->id, $order);
                                    // คืนค่า
                                    $ret['alert'] = Language::get('Saved successfully');
                                    $order['id'] = $borrow->id;
                                } else {
                                    // ใหม่
                                    $order['transaction_date'] = date('Y-m-d');
                                    $order['id'] = $db->insert($table_borrow, $order);
                                }
                                // อ่านรายการ items เก่า (ถ้ามี)
                                foreach ($db->select($table_borrow_items, [['borrow_id', $order['id']]]) as $item) {
                                    if (isset($items[$item['product_no']])) {
                                        $items[$item['product_no']] += $item;
                                    }
                                }
                                // ลบรายการเก่าออกก่อน
                                $db->delete($table_borrow_items, [
                                    ['borrow_id', $order['id']]
                                ], 0);
                                // save items
                                $n = 0;
                                foreach ($items as $save) {
                                    $save['id'] = $n;
                                    $save['borrow_id'] = $order['id'];
                                    $db->insert($table_borrow_items, $save);
                                    $n++;
                                }
                                // ตัดสต็อค
                                foreach ($items_stock as $save) {
                                    $this->db()->customQuery("UPDATE {$table_inventory_items} 
                                    SET stock = stock - {$save['stock']} 
                                    WHERE product_no = '{$save['product_no']}'");
                                }
                                if ($borrow->id == 0) {
                                    // ส่งอีเมลไปยังผู้ที่เกี่ยวข้อง
                                    $ret['alert'] = \Borrow\Email\Model::send($order);
                                    // log (ใหม่)
                                    $title = '{LNG_Borrow} &amp; {LNG_Return} {LNG_Add Borrow}';
                                } else {
                                    // log (แก้ไข)
                                    $title = '{LNG_Borrow} &amp; {LNG_Return} {LNG_Edit}';
                                }
                                // log
                                \Index\Log\Model::add($order['id'], 'borrow', 'Save', $title, $login['id'], $order);
                                // คืนค่า redirect ไปที่หน้ารายงาน เมนูอนุมัติ
                                $ret['location'] = $request->getUri()->postBack('index.php', ['module' => 'borrow-report', 'status' => 2, 'id' => null]);
                                // เคลียร์
                                $request->removeToken();
                            }
                        }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
