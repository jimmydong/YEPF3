<?php /* Smarty version Smarty-3.1.18, created on 2014-09-17 13:29:26
         compiled from "C:\xampp\htdocs\YEPF\_YEPF3.0\demo\_TEMPLATE\admin\index.html" */ ?>
<?php /*%%SmartyHeaderCode:673954191c36cd3197-14337662%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '3ec317942da036fdd7079ddf6ad3a23bb1b6cfca' => 
    array (
      0 => 'C:\\xampp\\htdocs\\YEPF\\_YEPF3.0\\demo\\_TEMPLATE\\admin\\index.html',
      1 => 1410856968,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '673954191c36cd3197-14337662',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'hello' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.18',
  'unifunc' => 'content_54191c36d2f223_91968963',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_54191c36d2f223_91968963')) {function content_54191c36d2f223_91968963($_smarty_tpl) {?><?php echo $_smarty_tpl->getSubTemplate ('include/header.html', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, null, array(), 0);?>

<body>
<h3>Admin</h3>
<?php echo $_smarty_tpl->tpl_vars['hello']->value;?>

</body>
</html><?php }} ?>
