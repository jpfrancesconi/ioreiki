<?php

namespace Drupal\mimemail\Plugin\Mail;

use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\mimemail\Utility\MimeMailFormatHelper;

/**
 * Defines the default Drupal mail backend, using PHP's native mail() function.
 *
 * @Mail(
 *   id = "mime_mail",
 *   label = @Translation("Mime Mail mailer"),
 *   description = @Translation("Sends MIME-encoded emails with embedded images and attachments.")
 * )
 */
class MimeMail extends PhpMail {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    if (is_array($message['body'])) {
      $message['body'] = implode("\n\n", $message['body']);
    }

    if (preg_match('/plain/', $message['headers']['Content-Type'])) {
      if (!$format = \Drupal::config('mimemail.settings')->get('format')) {
        $format = filter_fallback_format();
      }
      $message['body'] = check_markup($message['body'], $format);
    }

    $message = $this->prepareMessage($message);

    return $message;
  }

  /**
   * Prepares the message for sending.
   *
   * @param array $message
   *   An array containing the message data. The optional parameters are:
   *   - plain: Whether to send the message as plaintext only or HTML. If
   *     this evaluates to TRUE the message will be sent as plaintext.
   *   - plaintext: Optional plaintext portion of a multipart email.
   *   - attachments: An array of arrays which describe one or more attachments.
   *     Existing files can be added by path, dynamically-generated files can be
   *     added by content. The internal array contains the following elements:
   *      - filepath: Relative Drupal path to an existing file
   *        (filecontent is NULL).
   *      - filecontent: The actual content of the file (filepath is NULL).
   *      - filename: The filename of the file.
   *      - filemime: The MIME type of the file.
   *      The array of arrays looks something like this:
   *      Array
   *      (
   *        [0] => Array
   *        (
   *         [filepath] => '/sites/default/files/attachment.txt'
   *         [filecontent] => 'My attachment.'
   *         [filename] => 'attachment.txt'
   *         [filemime] => 'text/plain'
   *        )
   *      )
   *
   * @return array
   *   All details of the message.
   */
  protected function prepareMessage(array $message) {
    $module = $message['module'];
    $key = $message['key'];
    $to = $message['to'];
    $from = $message['from'];
    $subject = $message['subject'];
    $body = $message['body'];

    $headers = isset($message['params']['headers']) ? $message['params']['headers'] : [];
    $plain = isset($message['params']['plain']) ? $message['params']['plain'] : NULL;
    $plaintext = isset($message['params']['plaintext']) ? $message['params']['plaintext'] : NULL;
    $attachments = isset($message['params']['attachments']) ? $message['params']['attachments'] : [];

    $site_name = \Drupal::config('system.site')->get('name');
    //$site_mail = variable_get('site_mail', ini_get('sendmail_from'));
    $site_mail = \Drupal::config('system.site')->get('mail');
    /*$simple_address = variable_get('mimemail_simple_address', 0);*/

    // Override site mails default sender.
    if ((empty($from) || $from == $site_mail)) {
      $mimemail_name = \Drupal::config('mimemail.settings')->get('name');
      $mimemail_mail = \Drupal::config('mimemail.settings')->get('mail');
      $from = [
        'name' => !empty($mimemail_name) ? $mimemail_name : $site_name,
        'mail' => !empty($mimemail_mail) ? $mimemail_mail : $site_mail,
      ];
    }

    if (empty($body)) {
      // Body is empty, this is a plaintext message.
      $plain = TRUE;
    }
    // Try to determine recipient's text mail preference.
    elseif (is_null($plain)) {
      if (is_object($to) && isset($to->data['mimemail_textonly'])) {
        $plain = $to->data['mimemail_textonly'];
      }
      elseif (is_string($to) && \Drupal::service('email.validator')->isValid($to)) {
        if (is_object($account = user_load_by_mail($to)) && isset($account->data['mimemail_textonly'])) {
          $plain = $account->data['mimemail_textonly'];
          // Might as well pass the user object to the address function.
          $to = $account;
        }
      }
    }

    // Removing newline character introduced by _drupal_wrap_mail_line().
    $subject = str_replace(["\n"], '', trim(MailFormatHelper::htmlToText($subject)));

    $body = [
      '#theme' => 'mimemail_message',
      '#module' => $module,
      '#key' => $key,
      '#recipient' => $to,
      '#subject' => $subject,
      '#body' => $body,
    ];

    $body = \Drupal::service('renderer')->renderPlain($body);

    /*foreach (module_implements('mail_post_process') as $module) {
      $function = $module . '_mail_post_process';
      $function($body, $key);
    }*/

    //$plain = $plain || variable_get('mimemail_textonly', 0);
    $from = MimeMailFormatHelper::mimeMailAddress($from);
    $mail = MimeMailFormatHelper::mimeMailHtmlBody($body, $subject, $plain, $plaintext, $attachments);
    $headers = array_merge($message['headers'], $headers, $mail['headers']);

    //$message['to'] = MimeMailFormatHelper::mimeMailAddress($to, $simple_address);
    $message['to'] = MimeMailFormatHelper::mimeMailAddress($to);
    $message['from'] = $from;
    $message['subject'] = $subject;
    $message['body'] = $mail['body'];
    $message['headers'] = MimeMailFormatHelper::mimeMailHeaders($headers, $from);

    return $message;
  }

}
