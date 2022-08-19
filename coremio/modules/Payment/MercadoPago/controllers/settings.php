<?php
    if(!defined("CORE_FOLDER")) die();

    $lang           = $module->lang;
    $config         = $module->config;

    Helper::Load(["Money"]);

    $accessToken          = Filter::POST("accessToken"); // $_POST["accessToken"];
    $publicKey            = Filter::POST("publicKey"); // $_POST["publicKey"];
    $cpfcnpjfield         = Filter::POST("cpfcnpjfield"); // $_POST["publicKey"];
    $statement_descriptor = Filter::POST("statement_descriptor"); // $_POST["statement_descriptor"];
    $commission_rate      = Filter::init("POST/commission_rate","amount");
    $commission_rate      = str_replace(",",".",$commission_rate);


    $sets           = [];

    if($accessToken != $config["settings"]["accessToken"])
        $sets["settings"]["accessToken"] = $accessToken;

    if($publicKey != $config["settings"]["publicKey"])
        $sets["settings"]["publicKey"] = $publicKey;
    
    if($cpfcnpjfield != $config["settings"]["cpfcnpjfield"])
        $sets["settings"]["cpfcnpjfield"] = $cpfcnpjfield;

    if($commission_rate != $config["settings"]["commission_rate"])
        $sets["settings"]["commission_rate"] = $commission_rate;

    if($statement_descriptor != $config["settings"]["statement_descriptor"])
        $sets["settings"]["statement_descriptor"] = $statement_descriptor;


    if($sets){
        $config_result  = array_replace_recursive($config,$sets);
        $array_export   = Utility::array_export($config_result,['pwith' => true]);

        $file           = dirname(__DIR__).DS."config.php";
        $write          = FileManager::file_write($file,$array_export);

        $adata          = UserManager::LoginData("admin");
        User::addAction($adata["id"],"alteration","changed-payment-module-settings",[
            'module' => $config["meta"]["name"],
            'name'   => $lang["name"],
        ]);
    }

    echo Utility::jencode([
        'status' => "successful",
        'message' => $lang["success1"],
    ]);
