<?php

namespace Drupal\commerce\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use \Drupal\node\Entity\Node;

require_once(realpath(dirname(__FILE__) . '/../..') . '/vendor/autoload.php');
use \MercadoPago;
use \MercadoPago\SDK;


/**
 * Provides a form with two steps.
 *
 * This example demonstrates a multistep form with text input elements. We
 * extend FormBase which is the simplest form base class used in Drupal.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class OrdenPagoMultistepForm extends FormBase {

    var $curso;
    var $idCurso;
    var $OPId;

    function setCurso($curso) {
        $this->curso = $curso;
    }
    function getCurso() {
        return $this->curso;
    }
    function setIdCurso($idCurso) {
        $this->idCurso = $idCurso;
    }
    function getIdCurso() {
        return $this->idCurso;
    }
    function setOPId($op){
      $this->OPId = $op;
    }
    function getOPId(){
      return $this->OPId;
    }
   /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'orde_pago_multistep_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $curso = NULL) {

    //Add class
    $form['#attributes']['class'][] = 'suscripcion-curso-form';

    /** Check wich page we are in*/
    if ($form_state->has('page_num') && $form_state->get('page_num') == 2) {
      return self::confirmarPagoPageTwo($form, $form_state);
    }

    /** We are on the first page */
    $form_state->set('page_num', 1);

    $cursoTitle = $curso->getTitle();
    $this->setCurso($cursoTitle);

    $cursoId = $curso->id();
    $this->setIdCurso($cursoId);
    
    $form['curso_title'] = [
        '#type'   => 'item',
        '#markup' => $this->t('<h2>'.$cursoTitle.'</h2>'),
    ];
    

    $form['datos_comprador'] = [
        '#type'         => 'item',
        '#title'        => $this->t(' Paso 1: Datos del comprador '),
        '#description'  => $this->t('Por favor complete todos los datos requeridos asi podemos contactarnos con usted.'), 
    ];
    $form['nombres'] = [
        '#type'  => 'textfield',
        '#title' => $this->t('Nombres'),
        '#default_value' => $form_state->getValue('nombres', ''),
        //'#description' => $this->t('Enter your first name.'),
        '#required' => TRUE,
    ];
    $form['apellidos'] = [
        '#type'  => 'textfield',
        '#title' => $this->t('Apellidos'),
        '#default_value' => $form_state->getValue('apellidos', ''),
        '#required' => TRUE,
    ];
    $form['documento'] = [
        '#type'  => 'textfield',
        '#title' => $this->t('Documento'),
        '#default_value' => $form_state->getValue('documento', ''),
        '#required'  => TRUE,
    ];
    $form['mail'] = [
        '#type'  => 'email',
        '#title' => $this->t('Correo Electronico'),
        '#default_value' => $form_state->getValue('mail', ''),
        '#required'  => TRUE,
    ];
    
    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form. This is not required, but is convention.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Confirmar Mis Datos'),
      // Custom submission handler for page 1.
      '#submit' => ['::ordenPagoMultistepFormNextSubmit'],
      // Custom validation handler for page 1.
      '#validate' => ['::ordenPagoMultistepFormNextValidate'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $page_values = $form_state->get('page_values');

    /** change pay mothod to Transferencia Bancaria */
    $nodeOP = Node::load($this->getOPId());
    //set value for field
    //$nodeOP->field_orden_pago_metodo_pago = [1];
    $nodeOP->set('field_orden_pago_metodo_pago', ['target_id' => 5]);
    //save to update node
    $nodeOP->save();

    /*$this->messenger()->addMessage($this->t('The form has been submitted. name="@first @last", year of birth=@year_of_birth', [
      '@first' => $page_values['first_name'],
      '@last' => $page_values['last_name'],
      '@year_of_birth' => $page_values['birth_year'],
    ]));*/

    /** Send email whit transfer bank data*/
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'commerce';
    $key = 'finalizar_pago_transferencia';
    $to = 'jpfrancesconi@jpfrancesconi.com.ar';//$pages_values['mail'];//\Drupal::currentUser()->getEmail();
    $params['message'] = 'HOLA JUAN';//$entity->get('body')->value;
    $params['node_title'] = 'NODE TILTE';//$entity->label();
    $params['password'] = 'xeneize1905';
    $params['email'] = 'jpfrancesconi@jpfrancesconi.com.ar';
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;

    //$result = $mailManager->mail($module, $key, $to, $langcode, $params);
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result'] !== true) {
      drupal_set_message(t('Por algun motivo no hemos podido enviarle el correo con los datos, pero no se preocupe a la brevedad nos vamos a contactar con usted para resolverlo.'), 'error');
    }
    else {
      drupal_set_message(t('Por favor, revisa tu correo electronico, te hemos enviado las indicaciones para realizar el pago.'));
    }

    $this->messenger()->addMessage($this->t('Muchas gracias por suscribirse a: @curso', ['@curso' => $this->getCurso()]));
    //Redirect to Cursos Page View
    $form_state->setRedirect('view.cursos.cursos_page');
  }

  /**
   * Provides custom validation handler for page 1.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function ordenPagoMultistepFormNextValidate(array &$form, FormStateInterface $form_state) {
    $apellidos = $form_state->getValues('apellidos');
    $nombres = $form_state->getValues('nombres');
    $documento = $form_state->getValue('documento');
    $mail = $form_state->getValue('mail');
    $cursoId = $this->getIdCurso();

    if($apellidos == '') {
      $form_state->setErrorByName('apellidos', $this->t('Es necesario que ingrese su apellido'));
    }
    if($nombres == '') {
        $form_state->setErrorByName('nombres', $this->t('Es necesario que ingrese su nombre'));
    }
    if($documento == '') {
        $form_state->setErrorByName('documento', $this->t('Es necesario que ingrese su documento'));
    }
    if($mail == '') {
        $form_state->setErrorByName('mail', $this->t('Es necesario que ingrese su correo electronico'));
    }

    //We have to validate if the visito has curse assistan previously
    $database = \Drupal::database();
    //Select main table
    $query = $database->select('node_field_data', 'nfd');
    // field to select
    $query->fields('mail', ['field_orden_pago_mail_value']);
    //Join with mail field of Orden de Pago
    $query->join('node__field_orden_pago_mail','mail','mail.entity_id = nfd.nid');
    //Join with curso field of Orden de Pago
    $query->join('node__field_orden_pago_curso','curso','curso.entity_id = nfd.nid');
    // Where conditions
    $query->condition('nfd.type','orden_de_pago','=');
    $query->condition('mail.field_orden_pago_mail_value',$mail,'=');
    $query->condition('curso.field_orden_pago_curso_target_id',$cursoId,'=');

    //Execute query
    $result = $query->countQuery()->execute()->fetchField();

    if($result != 0){
      // Reject form and send message to visitor
      //$form_state->setErrorByName('mail', $this->t('Para el correo electronico ingresado ya hemos registrado una suscripcion a este curso.'));
    }
  }

  /**
   * Provides custom submission handler for page 1.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function ordenPagoMultistepFormNextSubmit(array &$form, FormStateInterface $form_state) {
    //$this->messenger()->addMessage($this->t('Documento: @doc', ['@doc' => $form_state->getValue('documento')]));
    
    //We have to create a new node: Orden de Pago
    //We need to validate if the pair(Curso, Mail) don't exist previously
    // Create node object with attached file.
    $apellidos = $form_state->getValue('apellidos');
    $nombres = $form_state->getValue('nombres');
    $documento = $form_state->getValue('documento');
    $mail = $form_state->getValue('mail');
    $fecha = date("Y-m-d");
    $title = 'OP_Curso_' . $this->getIdCurso() . '_' . $mail . '_' . $fecha;

    $nodeValues = [
        'type'  => 'orden_de_pago',
        'title' => $title,
    ];

    $node = \Drupal::entityTypeManager()->getStorage('node')->create($nodeValues);
    $node->set('field_orden_pago_apellidos', $apellidos);
    $node->set('field_orden_pago_nombres', $nombres);
    $node->set('field_orden_pago_documento', $documento);
    $node->set('field_orden_pago_mail', $mail);
    $node->set('field_orden_pago_estado', ['target_id' => 1]);
    $node->set('field_orden_pago_curso', [['target_id' => $this->getIdCurso()]]);
    $node->save();
    $this->setOPId($node->id());
    
    $form_state
      ->set('page_values', [
        // Keep only first step values to minimize stored data.
        'nombres' => $form_state->getValue('nombres'),
        'apellidos' => $form_state->getValue('apellidos'),
        'documento' => $form_state->getValue('documento'),
        'mail' => $form_state->getValue('mail'),
      ])
      ->set('page_num', 2)
      // Since we have logic in our buildForm() method, we have to tell the form
      // builder to rebuild the form. Otherwise, even though we set 'page_num'
      // to 2, the AJAX-rendered form will still show page 1.
      ->setRebuild(TRUE);
  }

  /**
   * Builds the second step form (page 2).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function confirmarPagoPageTwo(array &$form, FormStateInterface $form_state) {
    $form['datos_paso_1'] = [
        '#type'         => 'item',
        '#title'        => $this->t(' Paso 1: Datos del comprador '),
        '#description'  => $this->t('Curso: '. $this->getCurso()
            . '<br/>Cliente: '. $form_state->getValue('apellidos') . ', ' . $form_state->getValue('nombres') 
            . '<br/>Documento: ' . $form_state->getValue('documento')
            . '<br/>Correo electronico: ' . $form_state->getValue('mail')), 
    ];

    $form['datos_pago'] = [
        '#type'         => 'item',
        '#title'        => $this->t(' Paso 2: Configurar pago '),
        '#description'  => $this->t('Le enviaremos por correo electronico los datos del procedimiento de pago y acceso al curso.'), 
    ];

    /*$form['metodo'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Forma de Pago'),
      '#required' => TRUE,
    ];*/

    $form['metodo_pago'] = [
        '#title'    => $this->t("Seleccione el metodo de Pago"),
        '#type'     => 'radios',
        '#options'  => [
            '1' => 'MERCADO DE PAGO',
            '2' => 'TRANSFERENCIA BANCARIA',
        ],
        '#ajax'     => [
          // #ajax has two required keys: callback and wrapper.
          // 'callback' is a function that will be called when this element
          // changes.
          'callback'=> '::promptCallback',
          // 'wrapper' is the HTML id of the page element that will be replaced.
          'wrapper' => 'replace-textfield-container',
        ],
    ];
    // The 'replace-textfield-container' container will be replaced whenever
    // 'changethis' is updated.
    $form['replace_textfield_container'] = [
        '#type'       => 'container',
        '#attributes' => ['id' => 'replace-textfield-container'],
    ];
    /*$form['replace_textfield_container']['replace_textfield'] = [
        '#type' => 'textfield',
        '#title' => $this->t("Why"),
    ];*/
  
    // An AJAX request calls the form builder function for every change.
    // We can change how we build the form based on $form_state.
    $value = $form_state->getValue('metodo_pago');
    // The getValue() method returns NULL by default if the form element does
    // not exist. It won't exist yet if we're building it for the first time.
    if ($value !== NULL) { 
        if($value == '1'){
            $form['replace_textfield_container']['option_fieldset'] = [
                '#type' => 'details',
                //'#title' => $this->t("Metodo de Pago: '@value'", ['@value' => $value]),
                '#title' => $this->t("Metodo de Pago: Mercado de Pago"),
                '#open' => TRUE,
            ];
            // Get Pay button from Curso
            $cursoNode = \Drupal::entityTypeManager()->getStorage('node')->load($this->getIdCurso());
            //$link = $cursoNode->get('field_curso_link_de_pago')->getValue()[0]['uri'];
            
            // Agrega credenciales
            MercadoPago\SDK::setAccessToken('TEST-496942317272317-051417-63f39839fc682d6f66bcbd42cc361153-568122251');

            // Crea un objeto de preferencia
            $preference = new MercadoPago\Preference();

            // Crea un ítem en la preferencia
            $item = new MercadoPago\Item();
            $item->title = $cursoNode->title->value;//'Mi producto';
            $item->quantity = 1;
            $item->currency_id = "ARS";
            $item->unit_price = $cursoNode->field_curso_precio->getValue()[0]['value'];//75.56;
            $preference->items = array($item);

            $back = [
              "success" => 'http://localhost/reiki/mp/payresponse/success',
              "failure" => 'http://localhost/reiki/mp/payresponse/failure',
              "pending" => 'http://localhost/reiki/mp/payresponse/pending',
            ];
            $preference->back_urls = $back;
            $preference->external_reference = 1;

            $preference->save();

            /** change pay mothod to Transferencia Bancaria */
            $nodeOP = Node::load($this->getOPId());
            //set value for field
            //$nodeOP->field_orden_pago_metodo_pago = [1];
            $nodeOP->set('field_orden_pago_preference_id', $preference->id);
            //save to update node
            $nodeOP->save();

            $link = '<a href='.$preference->sandbox_init_point. '> Pagar </a>';
            
            $form['replace_textfield_container']['option_fieldset']['mercado_pago_image'] = [
              '#markup' => '<img src="../sites/default/files/mp_image.png" alt="mp_image"/><br/>',
            ];
            $form['replace_textfield_container']['option_fieldset']['mercado_pago'] = [
                //'#type'   => 'item',
                '#markup' => $link,
            ];
            
        } else if($value == '2'){
            $form['replace_textfield_container']['option_fieldset'] = [
                '#type' => 'details',
                //'#title' => $this->t("Metodo de Pago: '@value'", ['@value' => $value]),
                '#title' => $this->t("Metodo de Pago: Transferencia Bancaria"),
                '#open' => TRUE,
            ];
            $form['replace_textfield_container']['option_fieldset']['transferencia_image'] = [
              '#markup' => '<img src="../sites/default/files/banco_image.png" alt="banco_image"/><br/>',
            ];
            $mail = $form_state->getValue('mail');
            $form['replace_textfield_container']['option_fieldset']['transferencia'] = [
                '#type'   => 'item',
                '#markup' => $this->t("<p>Una vez realizado el pago, envienos el comprobante via E-Mail a sistemas@ideasorange.com</p>
                    <p>Presione FINALIZAR para para recibir las indicaciones al su correo electronico</p>"),
            ];
            $form['replace_textfield_container']['option_fieldset']['submit'] = [
                '#type' => 'submit',
                '#button_type' => 'primary',
                '#value' => $this->t('Finalizar'),
              ];
        }
    }


    /*$form['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Atras'),
      // Custom submission handler for 'Back' button.
      '#submit' => ['::confirmarPagoPageTwoBack'],
      // We won't bother validating the required 'color' field, since they
      // have to come back to this page to submit anyway.
      '#limit_validation_errors' => [],
    ];*/
    /*$form['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Finalizar'),
    ];*/

    return $form;
  }

  /**
   * Provides custom submission handler for 'Back' button (page 2).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function confirmarPagoPageTwoBack(array &$form, FormStateInterface $form_state) {
    $form_state
      // Restore values for the first step.
      ->setValues($form_state->get('page_values'))
      ->set('page_num', 1)
      // Since we have logic in our buildForm() method, we have to tell the form
      // builder to rebuild the form. Otherwise, even though we set 'page_num'
      // to 1, the AJAX-rendered form will still show page 2.
      ->setRebuild(TRUE);
  }

  /**
   * Handles switching the available regions based on the selected theme.
   */
  public function promptCallback($form, FormStateInterface $form_state) {
    return $form['replace_textfield_container'];
  }
}