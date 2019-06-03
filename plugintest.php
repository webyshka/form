<?php
/*
  Plugin Name: Plugintest
  Plugin URI:
  Description: Use shortcode [cr_custom_form]
  Version: 1.0
  Author:
  Author URI:
 */
function registration_form( $first_name, $last_name ,$email, $subject, $message ) {
    echo '
    <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
    <div>
    <label for="first_name">First Name <strong>*</strong></label>
    <input type="text" required name="first_name" value="' . ( isset( $_POST['first_name'] ) ? $first_name : null ) . '">
    </div>

    <div>
    <label for="last_name">Last Name <strong>*</strong></label>
    <input type="text" required name="last_name" value="' . ( isset( $_POST['last_name'] ) ? $last_name : null ) . '">
    </div>

    <div>
    <label for="email">E-mail <strong>*</strong></label>
    <input type="text" required name="email" value="' . ( isset( $_POST['email']) ? $email : null ) . '">
    </div>

    <div>
    <label for="subject">Subject</label>
    <input type="text" required name="subject" value="' . ( isset( $_POST['subject']) ? $subject : null ) . '">
    </div>

    <div>
    <label for="message">Message</label>
    <textarea required name="message">' . ( isset( $_POST['message']) ? $message : null ) . '</textarea>
    </div>
        <input type="submit" name="submit" value="Send"/>
    </form>
    ';
}

function registration_validation( $first_name, $last_name ,$email, $subject, $message )  {
    global $reg_errors;
    $reg_errors = new WP_Error;

    if ( filter_var($email, FILTER_VALIDATE_EMAIL) === false ) {
        $reg_errors->add('email_invalid', 'Email is not valid');
    }
    if ( is_wp_error( $reg_errors ) ) {

        foreach ( $reg_errors->get_error_messages() as $error ) {

            echo '<div>';
            echo '<strong>ERROR</strong>:';
            echo $error . '<br/>';
            echo '</div>';

        }

    }
}

function complete_send() {
    global $reg_errors, $first_name, $last_name ,$email, $subject, $message;
    if ( 1 > count( $reg_errors->get_error_messages() ) ) {
        if(wp_mail($email, $subject, $message)){
            logFile($email);
            create_contact_hubspot($email,$first_name, $last_name);
            echo 'Письмо отправлено.';
        } else {
        }
    }
}

function custom_send_function() {
    if ( isset($_POST['submit'] ) ) {
        registration_validation(
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['subject'],
            $_POST['email'],
            $_POST['message']
        );

        // проверка безопасности введенных данных
        global $first_name, $last_name ,$email, $subject, $message;
        $first_name =   sanitize_text_field( $_POST['first_name'] );
        $last_name  =   sanitize_text_field( $_POST['last_name'] );
        $subject   =   sanitize_text_field( $_POST['subject'] );
        $email   =   sanitize_text_field( $_POST['email'] );
        $message       =   esc_textarea( $_POST['message'] );

        // вызов @function complete_registration, чтобы создать пользователя
        // только если не обнаружено WP_error
        complete_send($first_name, $last_name ,$email, $subject, $message);
    }

    registration_form($first_name, $last_name ,$email, $subject, $message);
}

function logFile($textLog) {
    $text = "=======================\n";
    $text .= $textLog;//Выводим переданную переменную
    $text .= "\n". date('Y-m-d H:i:s') ."\n"; //Добавим актуальную дату после текста или дампа массива
    $fOpen = fopen(__DIR__ . '/log.txt','a');
    fwrite($fOpen, $text);
    fclose($fOpen);
}


add_shortcode( 'cr_custom_form', 'custom_send_shortcode' );

function custom_send_shortcode() {
    ob_start();
    custom_send_function();
    return ob_get_clean();
}

function create_contact_hubspot($email, $first_name, $last_name) {
    $arr = array(
        'properties' => array(
            array(
                'property' => 'email',
                'value' => $email
            ),
            array(
                'property' => 'firstname',
                'value' => $first_name
            ),
            array(
                'property' => 'lastname',
                'value' => $last_name
            ),
        )
    );
    $post_json = json_encode($arr);
    $hapikey = readline("Enter hapikey: (demo for the demo portal): ");
    $endpoint = 'https://api.hubapi.com/contacts/v1/contact?hapikey=demo';
    $ch = @curl_init();
    @curl_setopt($ch, CURLOPT_POST, true);
    @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
    @curl_setopt($ch, CURLOPT_URL, $endpoint);
    @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = @curl_exec($ch);
    $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errors = curl_error($ch);
    @curl_close($ch);
}