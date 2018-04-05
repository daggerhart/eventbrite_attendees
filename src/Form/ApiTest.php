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

    $form['test_api'] = [
      '#type' => 'button',
      '#value' => $this->t('Test Attendees Query'),
      '#ajax' => [
        'callback' => '::doAttendeesQuery',
        'event' => 'click',
        'wrapper' => 'eventbrite-test-query',
      ],
    ];

    $form['results'] = [
      '#markup' => '
        <div id="eventbrite-test-query">
          <strong>Effective URIs:</strong>
          <pre class="eventbrite-test-effective-uris"></pre>
          <hr>
          <strong>Results:</strong>
          <pre class="eventbrite-test-query-attendees"></pre>
        </div>',
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
    $response->addCommand(new HtmlCommand('.eventbrite-test-query-attendees', json_encode($attendees, JSON_PRETTY_PRINT)));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
