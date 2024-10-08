<?php
/**
 * @filesource modules/index/views/usage.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Usage;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;

/**
 * module=usage
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var array
     */
    private $actions;

    /**
     * ตาราง Log
     *
     * @param Request $request
     * @param array $login
     *
     * @return string
     */
    public function render(Request $request, $login)
    {
        // ค่าที่ส่งมา
        $params = [
            'from' => $request->request('from')->date(),
            'to' => $request->request('to')->date(),
            'act' => $request->request('act')->filter('a-zA-Z'),
            'mod' => $request->request('mod')->filter('a-z')
        ];
        $this->actions = \Index\Usage\Model::actions();
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable([
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Index\Usage\Model::toDataTable($params),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('usage_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('usage_sort', 'create_date desc')->toString(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => [$this, 'onRow'],
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => ['id'],
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => ['topic', 'name'],
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/index/model/usage/action',
            'actionCallback' => 'dataTableActionCallback',
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => [
                [
                    'name' => 'from',
                    'type' => 'date',
                    'text' => '{LNG_from}',
                    'value' => $params['from']
                ],
                [
                    'name' => 'to',
                    'type' => 'date',
                    'text' => '{LNG_to}',
                    'value' => $params['to']
                ],
                [
                    'name' => 'mod',
                    'text' => '{LNG_Module}',
                    'options' => [0 => '{LNG_all items}']+\Index\Usage\Model::modules(),
                    'value' => $params['mod']
                ],
                [
                    'name' => 'act',
                    'text' => '{LNG_Action}',
                    'options' => [0 => '{LNG_all items}'] + $this->actions,
                    'value' => $params['act']
                ]
            ],
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => [
                'create_date' => [
                    'text' => '{LNG_Date}',
                    'sort' => 'create_date'
                ],
                'topic' => [
                    'text' => '{LNG_Detail}'
                ],
                'name' => [
                    'text' => '{LNG_Name}',
                    'class' => 'center',
                    'sort' => 'name'
                ],
                'module' => [
                    'text' => '{LNG_Module}',
                    'class' => 'center',
                    'sort' => 'module'
                ],
                'action' => [
                    'text' => '{LNG_Action}',
                    'class' => 'center',
                    'sort' => 'action'
                ],
                'datas' => [
                    'text' => 'Datas',
                    'class' => 'center'
                ]
            ],
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => [
                'create_date' => [
                    'class' => 'nowrap'
                ],
                'name' => [
                    'class' => 'center nowrap'
                ],
                'module' => [
                    'class' => 'center'
                ],
                'action' => [
                    'class' => 'center'
                ]
            ]
        ]);
        /**
         * ประวัติการใช้งานจะต้องไม่มีใครแก้ไขได้แม้จะเป็น SuperAdmin
         */
        // if ($login['id'] == 1) {
        //     // Super Admin
        //     $table->actions = [
        //         [
        //             'id' => 'action',
        //             'class' => 'ok',
        //             'text' => '{LNG_With selected}',
        //             'options' => [
        //                 'delete' => '{LNG_Delete}'
        //             ]
        //         ]
        //     ];
        // }
        // save cookie
        setcookie('usage_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        setcookie('usage_sort', $table->sort, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array คืนค่า $item กลับไป
     */
    public function onRow($item, $o, $prop)
    {
        $topic = strip_tags($item['topic']);
        $item['topic'] = '<span class=one_line title="'.$topic.'">'.$topic.'</span>';
        $item['create_date'] = Date::format($item['create_date'], 'd M Y H:i');
        $item['action'] = isset($this->actions[$item['action']]) ? $this->actions[$item['action']] : $item['action'];
        return $item;
    }
}
