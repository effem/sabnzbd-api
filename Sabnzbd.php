<?php
namespace FMK\Sabnzbd;

use GuzzleHttp;

class Sabnzbd
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var integer
     */
    private $port;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var GuzzleHttp\Client
     */
    private $guzzle;

    /**
     * Sabnzbd constructor.
     *
     * @param string  $url
     * @param integer $port
     * @param string  $apiKey
     */
    public function __construct($url, $port, $apiKey)
    {
        $this->url = $url;
        $this->port = $port;
        $this->apiKey = $apiKey;

        $this->guzzle = new GuzzleHttp\Client();
    }

    /**
     * Url builder helper
     *
     * @param String $mode
     * @param array  $params
     *
     * @return string
     */
    private function buildUrl($mode, $params = [])
    {
        $urlParams = array_merge(['mode' => $mode, 'apikey' => $this->apiKey, 'output' => 'json'], $params);
        return 'http://' . $this->url . ':' . $this->port . "/api?" . http_build_query($urlParams);
    }

    /**
     * Returns all items currently in the queue and some additional information
     *
     * @param int $start
     * @param int $limit
     *
     * @return array
     */
    public function queue($start = 0, $limit = 100)
    {
        $url = $this->buildUrl('queue', [
            'start' => $start,
            'limit' => $limit
        ]);
        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['queue'];
    }

    /**
     * Returns all items in the history and some additional information
     *
     * @param string $category
     * @param int    $start
     * @param int    $limit
     * @param bool   $failedOnly
     *
     * @return array
     */
    public function history($category = '', $start = 0, $limit = 100, $failedOnly = false)
    {
        $url = $this->buildUrl('history', [
            'start'       => $start,
            'limit'       => $limit,
            'failed_only' => $failedOnly,
            'category'    => $category
        ]);
        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['history'];
    }

    /**
     * Returns the version number
     *
     * @return array
     */
    public function version()
    {
        $url = $this->buildUrl('version');
        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['version'];
    }

    /**
     * Returns all warnings that have been logged
     *
     * @return array
     */
    public function warnings()
    {
        $url = $this->buildUrl('warnings');
        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['warnings'];
    }

    /**
     * Returns all available catrgories
     *
     * @return array
     */
    public function categories()
    {
        $url = $this->buildUrl('get_cats');
        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['categories'];
    }

    /**
     * Returns all available scripts
     *
     * @return array
     */
    public function scripts()
    {
        $url = $this->buildUrl('get_scripts');
        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['categories'];
    }

    /**
     * Restarts the sabnzb deamon
     */
    public function restart()
    {
        $url = $this->buildUrl('restart');
        $this->guzzle->request('GET', $url);
    }


    /**
     * Deletes the entry with the given id from the queue
     *
     * @param string $entry
     *
     * @return boolean
     */
    public function deleteQueueEntry($entry)
    {
        return $this->deleteQueueEntries([$entry]);
    }

    /**
     * Deletes multiple entries with the given ids from the queue
     *
     * @param array $entries
     *
     * @return boolean
     */
    public function deleteQueueEntries($entries)
    {
        $url = $this->buildUrl('queue', [
            'name'  => 'delete',
            'value' => implode(',', $entries)
        ]);
        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['status'];
    }

    /**
     * Deletes all entries from the queue
     *
     * @return boolean
     */
    public function deleteAllQueueEntries()
    {
        $url = $this->buildUrl('queue', [
            'name'  => 'delete',
            'value' => 'all'
        ]);
        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['status'];
    }

    /**
     * Switches two entries in the queue
     *
     * @param string $first
     * @param string $second
     *
     * @return boolean
     */
    public function switchQueueEntries($first, $second)
    {
        $url = $this->buildUrl('switch', [
            'value'  => $first,
            'value2' => $second
        ]);
        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['result'];
    }

    /**
     * Pauses the whole queue
     *
     * @return boolean
     */
    public function pauseQueue()
    {
        $url = $this->buildUrl('set_pause');
        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['status'];
    }

    /**
     * Pauses the queue temporarely for the given amount of time.
     *
     * $time is the number of minutes the queue ist paused
     *
     * @param integer $time
     *
     * @return boolean
     */
    public function pauseQueueTemporary($time)
    {
        $url = $this->buildUrl('config', [
            'name'  => 'set_pause',
            'value' => $time

        ]);
        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['status'];
    }

    /**
     * Resumes the queue after it was paused
     *
     * @return boolean
     */
    public function resumeQueue()
    {
        $url = $this->buildUrl('resume');
        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['status'];
    }

    /**
     * Adds a file to the queue via the given link to the file
     *
     * @param string      $url
     * @param string|null $niceName
     * @param int         $priority
     * @param string      $category
     * @param int         $postProcessing
     * @param string      $script
     *
     * @return array
     */
    public function addUrl($url, $niceName = null, $priority = -100, $category = '', $postProcessing = 3, $script = '')
    {
        $params = [
            'name'     => $url,
            'priority' => $priority,
            'category' => $category,
            'pp'       => $postProcessing,
            'script'   => $script
        ];
        if ($niceName) {
            $params['nzbname'] = $niceName;
        }

        $url = $this->buildUrl('addurl', $params);
        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true);
    }

    public function shutdown()
    {
        throw new \Exception('Not Implemented');
    }

    public function addFile()
    {
        throw new \Exception('Not Implemented');
    }

    public function changeScript()
    {
        throw new \Exception('Not Implemented');
    }

    public function changeCategory()
    {
        throw new \Exception('Not Implemented');
    }

    public function queueCompleteAction()
    {
        throw new \Exception('Not Implemented');
    }

    public function changePostProcessing()
    {
        throw new \Exception('Not Implemented');
    }

    public function changePriority()
    {
        throw new \Exception('Not Implemented');
    }

    public function pauseQueueEntry()
    {
        throw new \Exception('Not Implemented');
    }

    public function resumeQueueEntry()
    {
        throw new \Exception('Not Implemented');
    }

    public function getQueueEntryFiles()
    {
        throw new \Exception('Not Implemented');
    }

    public function changeQueueEntryName()
    {
        throw new \Exception('Not Implemented');
    }

    public function pausePostProcessing()
    {
        throw new \Exception('Not Implemented');
    }

    public function resumePostProcessing()
    {
        throw new \Exception('Not Implemented');
    }

    public function deleteHistoryEntry($id, $withFiles = true)
    {
        return $this->deleteHistoryEntries([$id], $withFiles);
    }

    /**
     * Deletes the history entry with the given id
     *
     * @param string $ids
     * @param bool   $withFiles
     *
     * @return array
     */
    public function deleteHistoryEntries($ids, $withFiles = true)
    {
        $params = [
            'name'      => 'delete',
            'del_files' => $withFiles,
            'value'     => implode(',', $ids)
        ];
        $url = $this->buildUrl('history', $params);

        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['status'];
    }

    /**
     * Deletes all history entries
     *
     * @param bool $withFiles
     *
     * @return array
     */
    public function deleteAllHistoryEntries($withFiles = true)
    {
        $params = [
            'name'      => 'delete',
            'del_files' => $withFiles,
            'value'     => 'all'
        ];
        $url = $this->buildUrl('history', $params);

        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['status'];
    }

    /**
     * @param bool $withFiles
     *
     * @return array
     */
    public function deleteAllFailedHistoryEntries($withFiles = true)
    {
        $params = [
            'name'      => 'delete',
            'del_files' => $withFiles,
            'value'     => 'failed'
        ];
        $url = $this->buildUrl('history', $params);

        $request = $this->guzzle->request('GET', $url);

        return json_decode($request->getBody(), true)['status'];
    }
}