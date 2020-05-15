<?php

namespace Drupal\commerce\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CommerceController extends ControllerBase {

  public function getData(Node $node) {
    $user = User::load($this->currentUser()->id());
    $data['user_uid'] = $user->id();
    $data['user_uuid'] = $user->uuid();
    $data['node_type'] = $node->getType();
    $data['node_uuid'] = $node->uuid();

    $data['favorited'] = FALSE;
    $favorites = $user->field_favorites;
    if ($favorites) {
      foreach ($favorites as $favorite) {
        if ($favorite->entity->id() == $node->id()) {
          $data['favorited'] = TRUE;
          break;
        }
      }
    }
    return new JsonResponse($data);
  }

  public function getPayDetails(Request $request){

    $pid = $request->query->get('preference_id');

    //We have to validate if the visito has curse assistan previously
    $database = \Drupal::database();
    //Select main table
    $query = $database->select('node_field_data', 'nfd');
    // field to select
    $query->fields('nfd', ['nid']);
    //Join with mail field of Orden de Pago
    $query->join('node__field_orden_pago_preference_id','pid','pid.entity_id = nfd.nid');
    // Where conditions
    $query->condition('nfd.type','orden_de_pago','=');
    $query->condition('pid.field_orden_pago_preference_id_value',$pid,'=');

    //Execute query
    $result = $query->execute()->fetchField();

    /** change pay mothod to Transferencia Bancaria */
    $nodeOP = Node::load($result);
    //set value for field estado ACEPTADO
    $nodeOP->set('field_orden_pago_estado', ['target_id' => 2]);
    //save to update node
    $nodeOP->save();

    $message = '<h1>Muchas gracias </h1>'
              .'<p>Hemos recibido la confirmacion del pago nro:'. $request->query->get('merchant_order_id') .'</p>'
              .'<p>Preferencei id: '.$request->query->get('preference_id').'</p>'
              .'<p>Collection id: '.$request->query->get('collection_id').'</p>'
              .'<p>Collection status: '.$request->query->get('collection_status').'</p>'
              .'<p>Payment type: '.$request->query->get('payment_type').'</p>';
    return [
      '#markup' => $this->t($message),
    ];
  }

  public function getExampleRoute1(){
    return new RedirectResponse('node/3');
  }
}
