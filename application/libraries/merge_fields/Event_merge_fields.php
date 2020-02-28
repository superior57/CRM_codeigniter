<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Event_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
                [
                    'name'      => 'Event Title',
                    'key'       => '{event_title}',
                    'available' => [
                    ],
                    'templates' => [
                        'event-notification-to-staff',
                    ],
                ],
                [
                    'name'      => 'Event Description',
                    'key'       => '{event_description}',
                    'available' => [
                    ],
                    'templates' => [
                        'event-notification-to-staff',
                    ],
                ],
                [
                    'name'      => 'Start Date',
                    'key'       => '{event_start_date}',
                    'available' => [
                    ],
                    'templates' => [
                        'event-notification-to-staff',
                    ],
                ],
                [
                    'name'      => 'End Date',
                    'key'       => '{event_end_date}',
                    'available' => [
                    ],
                    'templates' => [
                        'event-notification-to-staff',
                    ],
                ],
                [
                    'name'      => 'Event Link',
                    'key'       => '{event_link}',
                    'available' => [
                    ],
                    'templates' => [
                        'event-notification-to-staff',
                    ],
                ],
            ];
    }

    /**
     * Calendar event merge fields
     * @param  object $event event
     * @return array
     */
    public function format($event)
    {
        $fields['{event_title}']       = $event->title;
        $fields['{event_description}'] = $event->description;
        $fields['{event_start_date}']  = _dt($event->start);
        $fields['{event_end_date}']    = $event->end ? _dt($event->end) : '';
        $fields['{event_link}']        = admin_url('utilities/calendar?eventid=' . $event->eventid);

        return hooks()->apply_filters('event_merge_fields', $fields, [
            'event' => $event,
         ]);
    }
}
