# Eventbrite Attendees

A simple Drupal 8 module that adds a new block to the system for showing a list of attendees to an Eventbrite event.

## Usage

* Install the module
* Visit `/admin/config/services/eventbrite_attendees` and provide your Eventbrite OAuth token
* Visit `/admin/structure/block` and add an Eventbrite Attendees block

## Features

* Template on the attendees-list level, and custom template suggestion through UI
* Cache JSON response list of attendees
* Token replacement for contextual node when placed on a node route

![Screenshot of block configuration](http://public.daggerhart.com/images/eventbrite-attendees-block-1.png)