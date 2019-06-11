<?php


namespace Inspector\Wordpress;


use Inspector\Inspector;

class InspectorWordpressWrapper extends Inspector
{
    /**
     * Flush data to the remote platform.
     *
     * @throws \Exception
     */
    public function flush()
    {
        $this->addEntries(SpanWordpressCollection::all());

        parent::flush();
    }
}