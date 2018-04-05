<?php

namespace Drupal\eventbrite_attendees\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class apiTestForm
 *
 * @package Drupal\eventbrite_attendees\Form
 */
class ApiTest extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eventbrite_attendees_test_api';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $oauth_token = $this->config('eventbrite_attendees.settings')->get('oauth_token');

    if (!$oauth_token) {
      drupal_set_message('No OAuth token found. Please visit the settings page and provide your personal OAuth token.', 'error');
      return $form;
    }

    $form['event_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event ID'),
    ];

    $form['attendees_query'] = [
      '#type' => 'button',
      '#value' => $this->t('Get Attendees Data'),
      '#ajax' => [
        'callback' => '::doAttendeesQuery',
        'event' => 'click',
        'wrapper' => 'eventbrite-test-query',
      ],
    ];
    $form['questions_query'] = [
      '#type' => 'button',
      '#value' => $this->t('Get Custom Questions'),
      '#ajax' => [
        'callback' => '::doQuestionsQuery',
        'event' => 'click',
        'wrapper' => 'eventbrite-test-query',
      ],
    ];
    $form['results_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Results'),
      '#attributes' => [
        'id' => 'eventbrite-test-query',
      ],
      'effective_uris' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#prefix' => '<strong>' . $this->t('Effective URIs') . '</strong>',
        '#attributes' => [
          'class' => ['eventbrite-test-effective-uris']
        ]
      ],
      'data' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#prefix' => '<strong>' . $this->t('Data') . '</strong>',
        '#attributes' => [
          'class' => ['eventbrite-test-data']
        ]
      ],
    ];

    return $form;
  }

  /**
   * Attempt to call the Eventbrite API for attendees of the given event id.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function doAttendeesQuery(array $form, FormStateInterface $form_state) {
    $api_client = \Drupal::service('eventbrite_attendees.api_client');
    $event_id = $form_state->getValue('event_id');
    $attendees = $api_client->getEventAttendees($event_id);

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('.eventbrite-test-effective-uris', implode("\n", $api_client->effectiveUris)));
    $response->addCommand(new HtmlCommand('.eventbrite-test-data', json_encode($attendees, JSON_PRETTY_PRINT)));
    return $response;
  }

  /**
   * Call the Eventbrite API for custom questions of the given event id.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function doQuestionsQuery(array $form, FormStateInterface $form_state) {
    $api_client = \Drupal::service('eventbrite_attendees.api_client');
    $event_id = $form_state->getValue('event_id');
    $attendees = $api_client->getEventQuestions($event_id);

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('.eventbrite-test-effective-uris', implode("\n", $api_client->effectiveUris)));
    $response->addCommand(new HtmlCommand('.eventbrite-test-data', json_encode($attendees, JSON_PRETTY_PRINT)));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
