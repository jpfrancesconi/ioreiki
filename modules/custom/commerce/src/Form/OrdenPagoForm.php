<?php

namespace Drupal\commerce\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Implements an example form.
 */
class OrdenPagoForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'orden_pago_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $curso = NULL) {
    
    $cursoTitle = $curso->getTitle();
    $cursoId = $curso->id();
    
    $form['curso_title'] = [
        '#type'   => 'item',
        '#markup' => $this->t('<h2>'.$cursoTitle.'</h2>'),
    ];
    

    $form['datos_comprador'] = [
        '#type'         => 'fieldset',
        '#title'        => $this->t(' Paso 1: Datos del comprador '),
        '#description'  => $this->t('Por favor complete todos los datos requeridos asi podemos contactarnos con usted.'), 
    ];
    $form['datos_comprador']['nombres'] = [
        '#type'  => 'textfield',
        '#title' => $this->t('Nombres'),
        '#required' => TRUE,
    ];
    $form['datos_comprador']['apellidos'] = [
        '#type'  => 'textfield',
        '#title' => $this->t('Apellidos'),
        '#required' => TRUE,
    ];
    $form['datos_comprador']['documento'] = [
        '#type'  => 'textfield',
        '#title' => $this->t('Documento'),
        '#required'  => TRUE,
    ];
    $form['datos_comprador']['mail'] = [
        '#type'  => 'email',
        '#title' => $this->t('Correo Electronico'),
        '#required'  => TRUE,
    ];
    $form['datos_comprador']['next1'] = [
        '#type'  => 'button',
        '#value' => $this->t('Confirmar y Pagar'),
    ];
    /*$form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];*/
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('phone_number')) < 3) {
      $form_state->setErrorByName('phone_number', $this->t('The phone number is too short. Please enter a full phone number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('Your phone number is @number', ['@number' => $form_state->getValue('phone_number')]));
  }

}
