<?php
// SDK do Mercado Pago
require __DIR__ . '/../vendor/autoload.php';

$preference = $module->get_mercadopago_preference($links["successful-page"],$links["failed-page"]);
?>
<div align="center">
    <div class="progresspayment">
        
            <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
       
        <br><h3 id="progressh3"><?php echo $module->lang["redirect-message"]; ?></h3>
        <h4>
            <div class='angrytext'>
                <strong><?php echo __("website/others/loader-text2"); ?></strong>
            </div>
        </h4>

    </div>
</div>
<script src="https://sdk.mercadopago.com/js/v2"></script>
<script type="text/javascript">
    const mp = new MercadoPago('<?php echo $module->get_public_key(); ?>');
    // Inicializa o checkout
    const checkout = mp.checkout({
    preference: {
        id: '<?php echo $preference->id; ?>'
    },
    autoOpen: true,
    });
</script>
