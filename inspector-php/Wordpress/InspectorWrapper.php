<?php


namespace Inspector\Wordpress;


use Inspector\Inspector;

class InspectorWrapper extends Inspector
{
    /**
     * Flush data to the remote platform.
     *
     * @throws \Exception
     */
    public function flush()
    {
        $this->addEntries(SpanCollection::all());

        parent::flush();
    }
}