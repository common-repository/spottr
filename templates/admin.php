<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>
<div class="scontainer">
    <div class="sheader">
        <img src="<?php echo esc_url(SPOTTR_PLUGIN_URL . 'assets/img/logoBlack.537be2f7.svg'); ?>" alt="">
    </div>
    <div class="scontent">
        <h3>Authentication</h3>
        <?php
        if (empty(get_option("spottr_userid"))) :
        ?>
            <p>Authenticate your Spottr account to get started.</p>
            <form action="" method="post" id="spottrForm">
                <table>
                    <tr>
                        <td>
                            <label for="email">Email</label>
                        </td>
                        <td>
                            <input type="email" name="email" id="email" placeholder="Email" required class="input">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="password">Password</label>
                        </td>
                        <td>
                            <input type="password" name="password" id="password" placeholder="Password" required class="input">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="submit" name="submit" value="Authenticate" class="button button-primary">
                        </td>
                    </tr>
                </table>
            </form>
        <?php
        else :
        ?>
            <p>
                <i class="fa fa-check-circle" style="color: #00b300;"></i> You are authenticated.
            </p>
            <?php
            if (get_option("spottr_content_imported") == 1) {
                echo '<p><i class="fa fa-check-circle" style="color: #00b300;"></i> Categories and tags have been imported.</p>';
            } else {
                echo '<p><i class="fa fa-times-circle" style="color: #ff0000;"></i> Issues importing categories and tags. <a href="javascript:;" class="spottr_content">Try again</a></p>';
            }
            ?>
            <a href="javascript:;" class="button button-primary spottrDisconnect" style="margin-top: 10px;">Disconnect</a>
        <?php
        endif;
        ?>
    </div>
</div>