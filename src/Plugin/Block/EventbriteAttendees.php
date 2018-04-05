<?php

namespace Drupal\eventbrite_attendees\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Eventbrite attendees list Block.
 *
 * @Block(
 *   id = "eventbrite_attendees",
 *   admin_label = @Translation("Eventbrite Attendees block")
 * )
 */
class EventbriteAttendees extends BlockBase implements BlockPluginInterface
{
  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $attendees = $this->getAttendees();

    if (empty($attendees)){
      return [];
    }

    $config = $this->getConfiguration();

    return array(
      '#theme' => [
        'eventbrite_attendees__'.$config['template_suggestion'],
        'eventbrite_attendees'
      ],
      '#attendees' => $attendees
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['event_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Eventbrite event ID'),
      '#description' => $this->t(''),
      '#required' => TRUE,
      '#default_value' => isset($config['event_id']) ? $config['event_id'] : '',
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['token_tree'] = [
        '#type' => 'container',
        'token_tree_link' => [
          '#theme' => 'token_tree_link',
          '#token_types' => array_keys($this->getTokenData()),
        ],
      ];
    }

    $form['cache_response'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cache JSON response'),
      '#description' => $this->t('If this event is over, there is no need to continue querying against the API.'),
      '#default_value' => !empty($config['cache_response']),
    ];

    $form['cache_length'] = [
      '#type' => 'select',
      '#title' => $this->t('Cache JSON response time lenth'),
      '#description' => $this->t('If this event is over, there is no need to continue querying against the API.'),
      '#default_value' => !empty($config['cache_length']) ? $config['cache_length']: -1,
      '#options' => [
        -1 => $this->t('Forever'),
        1800 => $this->t('30 minutes'),
        3600 => $this->t('1 hour'),
        3600 * 24 => $this->t('1 day'),
      ],
    ];

    $form['template_suggestion'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template suggestion'),
      '#description' => $this->t('Provide a custom suffix for a block template suggestion'),
      '#required' => false,
      '#default_value' => isset($config['template_suggestion']) ? $config['template_suggestion'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    $this->configuration['event_id'] = $form_state->getValue('event_id');
    $this->configuration['cache_response'] = $form_state->getValue('cache_response');
    $this->configuration['cache_length'] = $form_state->getValue('cache_length');
    $this->configuration['template_suggestion'] = $form_state->getValue('template_suggestion');
  }

  /**
   * Get attendees array
   *
   * @return array
   */
  function getAttendees()
  {
    $config = $this->getConfiguration();
    $event_id = $config['event_id'];

    if (\Drupal::moduleHandler()->moduleExists('token')){
      $token = \Drupal::token();
      $event_id = $token->replace($event_id, $this->getTokenData(), [ 'clear' => true ]);
    }

    if (empty($event_id) || !is_numeric($event_id)){
      return [];
    }

    $do_cache = !empty($config['cache_response']);
    $cache_key = "eventbrite_attendees_block.".md5($event_id.$this->getPluginId());

    if ($do_cache){
      $cache = \Drupal::cache()->get($cache_key);

      if ($cache){
        return $cache->data;
      }
    }

    $api_client = \Drupal::service('eventbrite_attendees.api_client');
    $attendees = $api_client->getEventAttendees($event_id);

    if ($do_cache){
      \Drupal::cache()->set($cache_key, $attendees, $config['cache_length']);
    }

    return $attendees;
  }

  /**
   * Returns available context as token data.
   *
   * @return array
   *   An array with token data values keyed by token type.
   */
  protected function getTokenData()
  {
    $data = [];

    $node = \Drupal::routeMatch()->getParameter('node');

    if ($node){
      $data['node'] = $node;
    }

    return $data;
  }
}
