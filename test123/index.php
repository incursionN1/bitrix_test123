<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle('Тестовое');
$APPLICATION->SetAdditionalCSS(__DIR__.'..\local\components\castomComponens\dealList\templates\.default\style.css');
$APPLICATION->IncludeComponent(
    "castomComponens:dealList",
    ".default",
    array(
    ),
    false
);
?>
<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>