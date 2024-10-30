<?php

if(isset($_POST['submit'])) {

    $modalReadmoreBootstrapModal = $_POST['modalReadmoreBootstrapModal'];  
    $modalReadmoreModalWidth     = $_POST['modalReadmoreModalWidth']; 
    update_option('modalReadmoreBootstrapModal', $modalReadmoreBootstrapModal);
    
    if(is_numeric($modalReadmoreModalWidth))
        update_option('modalReadmoreModalWidth', $modalReadmoreModalWidth);
    else
        $modalReadmoreModalWidth = get_option('modalReadmoreModalWidth');
        
    echo '<div class="updated">'.__('Settings Saved', 'modal_readmore').'</div>';
    
} else {
    
    $modalReadmoreBootstrapModal = get_option('modalReadmoreBootstrapModal');
    $modalReadmoreModalWidth     = get_option('modalReadmoreModalWidth');
    
}

?>

<div class="wrap">
    <?php echo "<h2>".__('Modal Read More Settings', 'modal_readmore')."</h2>"; ?>
     
    <form name="modalReadmoreForm" method="post" action="<?php echo str_replace('%7E', '~', $_SERVER['REQUEST_URI']); ?>">
        <p>
            <?php echo __('Load Bootstrap Modal?', 'modal_readmore'); ?>
            <select name="modalReadmoreBootstrapModal">
                <?php if($modalReadmoreBootstrapModal == '1'): ?>
                <option value="1" selected><?php echo __('Yes', 'modal_readmore'); ?></option>
                <option value="0"><?php echo __('No', 'modal_readmore'); ?></option>
                <?php else: ?>
                <option value="1"><?php echo __('Yes', 'modal_readmore'); ?></option>
                <option value="0" selected><?php echo __('No', 'modal_readmore'); ?></option>
                <?php endif; ?>
            </select>
            <?php echo __('(select no ONLY if bootstrap is already loaded in your theme)', 'modal_readmore'); ?>
        </p>
        <p>
            <?php echo __('Modal Window Width:', 'modal_readmore'); ?>
            <input type="number" name="modalReadmoreModalWidth" value="<?php echo $modalReadmoreModalWidth; ?>" />
            <?php echo __('(in px)', 'modal_readmore'); ?>
        </p>
        <p class="submit">
            <input class="button button-primary" type="submit" name="submit" value="<?php echo __('Save Settings', 'modal_readmore') ?>" />
        </p>
    </form>
</div>