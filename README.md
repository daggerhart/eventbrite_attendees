# Eventbrite Attendees

A simple Drupal 8 module that adds a new block to the system for showing a list of attendees to an Eventbrite event.

## Usage

* Install the module
* Visit `/admin/config/services/eventbrite_attendees` and provide your Eventbrite OAuth token
* Visit `/admin/structure/block` and add an Eventbrite Attendees block
* Provide an Eventbrite Event ID manually, or use a node field token that contains the ID.

## Features

* Template on the attendees-list level, and custom template suggestion through UI
* Cache JSON response list of attendees
* Token replacement for contextual node when placed on a node route

![Screenshot of block configuration](http://public.daggerhart.com/images/eventbrite-attendees-block-1.png)


### JSON Response caching

Due to the way Eventbrite provides data from its API (paginated), this module implements some simple caching for the provided response. Since past events will never have new attendees, the “Forever” cache option makes it so those blocks should never need to call the Eventbrite API again.


### Templates

This module provides a level of templates for the output list for extra customization. Override the default template in your theme the normal-way, by copying the template from within this module to your theme and modifying it.

Simple modified template: ![simple modified template](http://cdn2.daggerhart.com/wp-content/uploads/dcavl-attendees-past.jpg)

Some times you may desire to have one list of attendees look one way, and another list appear differently. For this case the module has an option that allows you to provide a custom template suggestion within the block UI. Simply edit one of your blocks and provide a new template suggestion (suffix) in the appropriate field. Then copy the default template to your theme with the new suggestion as the template suffix (following Drupal standards).

For example, if you would like a block on your home page to have a custom template, edit that block add `home_page` as the template suggestion. Then copy this module's default template to your theme as a file named `eventbrite-attendees--home-page.html.twig` and clear your site's theme cache. The new template should then be overriding the output for just that home page block.

More advanced template: ![advanced template](http://cdn2.daggerhart.com/wp-content/uploads/dcavl-attendees-current.jpg)