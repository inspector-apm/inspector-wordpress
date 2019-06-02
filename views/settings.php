<div class="wrap">
    <a href="https://www.inspector.dev" target="_blank">
        <img src="<?=plugins_url( '../assets/images/logo-horizontal.png', __FILE__ ) ?>" style="width: 200px;"/>
    </a>

    <br/><br/>

    <form method="post" action="options.php">
        <?php settings_fields( 'inspector-settings' ); ?>
        <?php do_settings_sections( 'inspector-settings' ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    API KEY <br/>
                    Create a new project in your Inspector dashboard to obtain a valid Key.
                </th>
                <td>
                    <input
                        style="width: 80%;"
                        type="text"
                        name="inspector_api_key"
                        value="<?=esc_attr(get_option('inspector_api_key')); ?>"
                        placeholder="Paste here your project api key..."
                    />
                    <br/><br/>
                    <a href="https://app.inspector.dev/home" target="_blank">
                        Go to Inspector dashboard.
                    </a>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    Activate <br/>
                    Enable/disable monitoring.
                </th>
                <td>
                    <input
                        type="checkbox"
                        name="inspector_enable"
                        value="1"
                        <?php if(esc_attr(get_option('inspector_enable'))) echo 'checked' ?>
                    />
                    Check this flag to activate monitoring
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>