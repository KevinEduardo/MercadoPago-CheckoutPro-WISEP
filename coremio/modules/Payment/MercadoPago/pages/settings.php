<?php
    if(!defined("CORE_FOLDER")) die();
    $LANG           = $module->lang;
    $CONFIG         = $module->config;
    $callback_url   = Controllers::$init->CRLink("payment",['MercadoPago',$module->get_auth_token(),'callback']);
    $success_url    = Controllers::$init->CRLink("pay-successful");
    $failed_url     = Controllers::$init->CRLink("pay-failed");
?>
<form action="<?php echo Controllers::$init->getData("links")["controller"]; ?>" method="post" id="MercadoPago">
    <input type="hidden" name="operation" value="module_controller">
    <input type="hidden" name="module" value="MercadoPago">
    <input type="hidden" name="controller" value="settings">

    <div class="blue-info" style="margin-bottom:20px;">
        <div class="padding15">
            <i class="fa fa-info-circle" aria-hidden="true"></i>
            <p><?php echo $LANG["description"]; ?></p>
        </div>
    </div>

    <div class="formcon">
        <div class="yuzde30"><?php echo $LANG["accessToken"]; ?></div>
        <div class="yuzde70">
            <input type="text" name="accessToken" value="<?php echo $CONFIG["settings"]["accessToken"]; ?>">
            <span class="kinfo"><?php echo $LANG["accessToken-desc"]; ?></span>
        </div>
    </div>

    <div class="formcon">
        <div class="yuzde30"><?php echo $LANG["publicKey"]; ?></div>
        <div class="yuzde70">
            <input type="text" name="publicKey" value="<?php echo $CONFIG["settings"]["publicKey"]; ?>">
            <span class="kinfo"><?php echo $LANG["publicKey-desc"]; ?></span>
        </div>
    </div>

    <div class="formcon">
        <div class="yuzde30"><?php echo $LANG["cpfcnpjfield"]; ?></div>
        <div class="yuzde70">
            <input type="text" name="cpfcnpjfield" value="<?php echo $CONFIG["settings"]["cpfcnpjfield"]; ?>">
            <span class="kinfo"><?php echo $LANG["cpfcnpjfield-desc"]; ?></span>
        </div>
    </div>

    <div class="formcon">
        <div class="yuzde30"><?php echo $LANG["statement_descriptor"]; ?></div>
        <div class="yuzde70">
            <input type="text" name="statement_descriptor" value="<?php echo $CONFIG["settings"]["statement_descriptor"]; ?>">
            <span class="kinfo"><?php echo $LANG["statement_descriptor-desc"]; ?></span>
        </div>
    </div>

    <div class="formcon">
        <div class="yuzde30"><?php echo $LANG["commission-rate"]; ?></div>
        <div class="yuzde70">
            <input type="text" name="commission_rate" value="<?php echo $CONFIG["settings"]["commission_rate"]; ?>" style="width: 80px;">
            <span class="kinfo"><?php echo $LANG["commission-rate-desc"]; ?></span>
        </div>
    </div>

    
    <div class="formcon">
        <div class="yuzde30">Callback URL</div>
        <div class="yuzde70">
            <span style="font-size:13px;font-weight:600;" class="selectalltext"><?php echo $callback_url; ?></span>
        </div>
    </div>

    <div class="formcon">
        <div class="yuzde30">Success URL</div>
        <div class="yuzde70">
            <span style="font-size:13px;font-weight:600;" class="selectalltext"><?php echo $success_url; ?></span>
        </div>
    </div>

    <div class="formcon">
        <div class="yuzde30">Failed URL</div>
        <div class="yuzde70">
            <span style="font-size:13px;font-weight:600;" class="selectalltext"><?php echo $failed_url; ?></span>
        </div>
    </div>


    <div style="float:right;" class="guncellebtn yuzde30"><a id="MercadoPago_submit" href="javascript:void(0);" class="yesilbtn gonderbtn"><?php echo $LANG["save-button"]; ?></a></div>

</form>


<script type="text/javascript">
    $(document).ready(function(){

        $("#MercadoPago_submit").click(function(){
            MioAjaxElement($(this),{
                waiting_text:waiting_text,
                progress_text:progress_text,
                result:"MercadoPago_handler",
            });
        });

    });

    function MercadoPago_handler(result){
        if(result != ''){
            var solve = getJson(result);
            if(solve !== false){
                if(solve.status == "error"){
                    if(solve.for != undefined && solve.for != ''){
                        $("#MercadoPago "+solve.for).focus();
                        $("#MercadoPago "+solve.for).attr("style","border-bottom:2px solid red; color:red;");
                        $("#MercadoPago "+solve.for).change(function(){
                            $(this).removeAttr("style");
                        });
                    }
                    if(solve.message != undefined && solve.message != '')
                        alert_error(solve.message,{timer:5000});
                }else if(solve.status == "successful"){
                    alert_success(solve.message,{timer:2500});
                }
            }else
                console.log(result);
        }
    }
</script>
