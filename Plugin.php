<?php
/**
 * NoReferrer
 * @package NoReferrer
 * @author CatiZ
 * @version 1.0.1
 * @link https://www.catiz.cn
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class NoReferrer_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法
     */
    public static function activate()
    {
        // 添加到文章编辑页面的回调
        Typecho_Plugin::factory('admin/write-post.php')->option = array('NoReferrer_Plugin', 'renderOption');
        Typecho_Plugin::factory('admin/write-page.php')->option = array('NoReferrer_Plugin', 'renderOption');
        // 前端输出的回调
        Typecho_Plugin::factory('Widget_Archive')->header = array('NoReferrer_Plugin', 'injectHeadCode');
        // 绑定保存文章时的回调
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('NoReferrer_Plugin', 'saveFields');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishPublish = array('NoReferrer_Plugin', 'saveFields');
   
    }

    /**
     * 禁用插件方法
     */
    public static function deactivate()
    {
    }

    /**
     * 获取插件配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 个人用户配置面板
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 在文章编辑页面的高级选项中添加选项
     */
    public static function renderOption()
    {
        $request = Typecho_Request::getInstance();
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        $cid = isset($request->cid) ? intval($request->cid) : null;
        $value = '';

        if ($cid) {
            // 查询是否已存在字段值
            $row = $db->fetchRow($db->select('str_value')->from($prefix . 'fields')->where('cid = ? AND name = ?', $cid, 'no_referrer'));
            $value = $row ? $row['str_value'] : '';
        }

        echo '<section class="typecho-post-option">';
        echo '<label class="typecho-label" style="margin: 1em 0 1em;">';
        echo '<input type="checkbox" name="no_referrer" value="1" ' . ($value == '1' ? 'checked="checked"' : '') . '>';
        echo ' 添加no-referrer标签';
        echo '</label>';
        echo '</section>';

    }

    public static function saveFields($post, $obj)
    {
        $cid = $obj->cid;
        $request = Typecho_Request::getInstance();
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        $value = isset($request->no_referrer) ? '1' : '0';
        // 查询是否已存在该字段
        $exists = $db->fetchRow($db->select()->from($prefix . 'fields')->where('cid = ? AND name = ?', $cid, 'no_referrer'));

        if ($exists) {
            // 更新字段值
            $db->query($db->update($prefix . 'fields')->rows(array('str_value' => $value))->where('cid = ? AND name = ?', $cid, 'no_referrer'));
        } else {
            // 插入新的字段
            $db->query($db->insert($prefix . 'fields')->rows(array(
                'cid' => $cid,
                'name' => 'no_referrer',
                'type' => 'str',
                'str_value' => $value
            )));
        }
    }

    /**
     * 插入 HTML 代码到页面的 <head> 中
     */
    public static function injectHeadCode()
    {
        $cid = Typecho_Widget::widget('Widget_Archive')->cid;
        $value = self::getFieldValue('no_referrer', $cid);
        
        if ($value == '1') {
            echo '<meta name="referrer" content="no-referrer">';
        }
    }

    /**
     * 获取字段值
     */
    public static function getFieldValue($name, $cid)
    {
        if (!$cid) return '';

        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        $row = $db->fetchRow($db->select('str_value')->from($prefix . 'fields')->where('cid = ? AND name = ?', $cid, $name));
        return $row ? $row['str_value'] : '';
    }
    
}
