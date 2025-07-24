<?php
session_start(); // ابدأ الجلسة للوصول إلى متغيرات الجلسة

// إلغاء جميع متغيرات الجلسة
$_SESSION = array();

// إذا كان سيتم استخدام ملف تعريف الارتباط الخاص بالجلسة، قم بحذفه أيضاً
// ملاحظة: هذا سيفقد الجلسة الحالية
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// مسح أي كوكيز "تذكرني"
setcookie('remember_user_id', '', time() - 3600, "/");
setcookie('remember_username', '', time() - 3600, "/");

// تدمير الجلسة
session_destroy();

// إعادة التوجيه إلى صفحة تسجيل الدخول
header("Location: user-login.php"); // تم التغيير هنا
exit();
?>