<?php
/**
 * @filesource modules/borrow/views/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Borrow\Index;

use Kotchasan\Html;

/**
 * module=borrow
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์ม ยืมพัสดุ
     *
     * @param object $index
     * @param array $login
     *
     * @return string
     */
    public function render($index, $login)
    {
        $form = Html::create('form', [
            'id' => 'order_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/borrow/model/index/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ]);
        $fieldset = $form->add('fieldset', [
            'title' => '{LNG_Transaction details}',
            'titleClass' => 'icon-cart'
        ]);
        $groups = $fieldset->add('groups');
        // borrow_no
        $groups->add('text', [
            'id' => 'borrow_no',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width50',
            'label' => '{LNG_Transaction No.}',
            'placeholder' => '{LNG_Leave empty for generate auto}',
            'value' => $index->borrow_no,
            'readonly' => true
        ]);
        $groups = $fieldset->add('groups');
        // transaction_date
        $groups->add('date', [
            'id' => 'transaction_date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width50 date-readonly',
            'label' => '{LNG_Transaction date}',
            'value' => $index->transaction_date,
            'readonly' => true
        ]);
        // borrow_date
        $groups->add('date', [
            'id' => 'borrow_date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width50',
            'label' => '{LNG_Borrowed date}',
            'value' => $index->borrow_date
        ]);
        // return_date
        // $groups->add('date', [
        //     'id' => 'return_date',
        //     'labelClass' => 'g-input icon-calendar',
        //     'itemClass' => 'width50',
        //     'label' => '{LNG_Date of return}',
        //     'value' => $index->return_date
        // ]);
        $groups = $fieldset->add('groups');
        // borrower_emp_id
        $groups->add('number', [
            'id' => 'borrower_emp_id',
            'labelClass' => 'g-input icon-profile',
            'itemClass' => 'width50',
            'label' => '{LNG_Borrower employee id}',
            'value' => $index->borrower_emp_id,
            'maxlength' => 6,
            'required' => true
        ]);
        // borrower_dept_id
        $groups->add('text', [
            'id' => 'borrower_dept_id',
            'labelClass' => 'g-input icon-profile',
            'itemClass' => 'width50',
            'label' => '{LNG_Borrower department id}',
            'value' => $index->borrower_dept_id,
            'maxlength' => 4,
            'required' => true
        ]);
        $groups = $fieldset->add('groups');
        // borrower_fname
        $groups->add('text', [
            'id' => 'borrower_fname',
            'labelClass' => 'g-input icon-personnel',
            'itemClass' => 'width50',
            'label' => '{LNG_Borrower first name}',
            'value' => $index->borrower_fname,
            'maxlength' => 50,
            'required' => true
        ]);
        // borrower_lname
        $groups->add('text', [
            'id' => 'borrower_lname',
            'labelClass' => 'g-input',
            'itemClass' => 'width50',
            'label' => '{LNG_Borrower last name}',
            'value' => $index->borrower_lname,
            'maxlength' => 50,
            'required' => true
        ]);
        $groups = $fieldset->add('groups');
        // borrower_phone
        $groups->add('number', [
            'id' => 'borrower_phone',
            'labelClass' => 'g-input icon-phone',
            'itemClass' => 'width20',
            'label' => '{LNG_Borrower phone}',
            'value' => $index->borrower_phone,
            'maxlength' => 15,
            'required' => true
        ]);
        // borrower_remark
        $groups->add('text', [
            'id' => 'borrower_remark',
            'labelClass' => 'g-input icon-comments',
            'itemClass' => 'width80',
            'label' => '{LNG_Borrower remark}',
            'value' => $index->borrower_remark,
            'maxlength' => 255,
        ]);
        $groups = $fieldset->add('groups');
        // inventory_quantity
        $groups->add('number', [
            'id' => 'inventory_quantity',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width20',
            'label' => '{LNG_Quantity}',
            'value' => 1
        ]);
        // inventory
        $groups->add('text', [
            'id' => 'inventory',
            'labelClass' => 'g-input icon-barcode',
            'itemClass' => 'width80',
            'label' => '{LNG_Equipment}/{LNG_Serial/Registration No.}',
            'title' => '{LNG_Equipment}',
            'placeholder' => '{LNG_Find equipment by} {LNG_Equipment}, {LNG_Serial/Registration No.}'
        ]);
        $table = '<table class="fullwidth"><thead><tr>';
        $table .= '<th>{LNG_Detail}</th>';
        $table .= '<th>{LNG_Serial/Registration No.}</th>';
        $table .= '<th class=center>{LNG_Quantity}</th>';
        $table .= '<th class=center>{LNG_Unit}</th>';
        $table .= '<th></th>';
        $table .= '</tr></thead><tbody id=tb_products>';
        foreach (\Borrow\Index\Model::items($index->id) as $item) {
            $table .= '<tr'.($index->id == 0 ? ' class=hidden' : '').'>';
            $table .= '<td><label class="g-input"><input type=text name=topic[] value="'.$item['topic'].'" readonly></label></td>';
            $table .= '<td><label class="g-input"><input type=text name=product_no[] value="'.$item['product_no'].'" readonly></label></td>';
            $table .= '<td><label class="g-input"><input type=text name=quantity[] size=2 value="'.$item['quantity'].'" max="'.(empty($item['count_stock']) ? 2147483647 : $item['stock']).'" class="num"></label></td>';
            $table .= '<td><label class="g-input"><input type=text name=unit[] size="5" value="'.$item['unit'].'" readonly></label></td>';
            $table .= '<td><a class="button wide delete notext"><span class=icon-delete></span></a></td>';
            $table .= '</tr>';
        }
        $table .= '</tbody>';
        $table .= '</table>';
        $fieldset->add('div', [
            'class' => 'item',
            'innerHTML' => $table
        ]);
        $fieldset = $form->add('fieldset', [
            'class' => 'submit right'
        ]);
        // submit
        $fieldset->add('submit', [
            'class' => 'button ok large',
            'id' => 'order_submit',
            'value' => '{LNG_Save}'
        ]);
        // borrow_id
        $fieldset->add('hidden', [
            'id' => 'borrow_id',
            'value' => $index->id
        ]);
        // Javascript
        $form->script('initBorrowIndex();');
        // คืนค่า HTML
        return $form->render();
    }
}
