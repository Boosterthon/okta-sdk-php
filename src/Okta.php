<?php

/******************************************************************************
 * Copyright 2017 Okta, Inc.                                                  *
 *                                                                            *
 * Licensed under the Apache License, Version 2.0 (the "License");            *
 * you may not use this file except in compliance with the License.           *
 * You may obtain a copy of the License at                                    *
 *                                                                            *
 *      http://www.apache.org/licenses/LICENSE-2.0                            *
 *                                                                            *
 * Unless required by applicable law or agreed to in writing, software        *
 * distributed under the License is distributed on an "AS IS" BASIS,          *
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.   *
 * See the License for the specific language governing permissions and        *
 * limitations under the License.                                             *
 ******************************************************************************/

namespace Okta;

use Okta\DataStore\DefaultDataStore;
use Okta\Groups\Collection as GroupCollection;
use Okta\Logs\Collection as LogCollection;
use Okta\Groups\Group;
use Okta\Logs\LogEvent;
use Okta\Users\Collection as UserCollection;
use Okta\Users\User;

class Okta
{

    const VERSION = '1.3.0';

    private $responseHeaders;

    public function __construct(Client $client = null, DefaultDataStore $dataStore = null)
    {
        $this->client = $client ?: Client::getInstance();
        $this->dataStore = $dataStore ?: $this->client->getDataStore();
    }

    public function getUsers(array $options = [])
    {
        $result = $this->dataStore->getCollection(
            '/api/v1/users',
            User::class,
            UserCollection::class,
            $options
        );
        $this->responseHeaders = $this->dataStore->responseHeaders;
        return $result;
    }


    /**
     * returns true if previous call has a next page
     *
     * @return array
     */
    private function getNextOptions(): array
    {
        $ret = [];
        // return if a prior user page has not been accessed
        if (!$this->responseHeaders && $this->responseHeaders['link']) {
            return $ret;
        }

        // return false if there is no next on previous call response headers
        foreach ($this->responseHeaders['link'] as $link) {
            if (preg_match('/rel=\"next\"/', $link)) {
                if (preg_match('/after=(.*)&/', $link, $match)) {
                    $ret['after'] = $match[1];
                }
                if (preg_match('/limit=(.*)>/', $link, $match)) {
                    $ret['limit'] = $match[1];
                }
            }
        }
        return $ret;
    }

    /**
     * returns the result of the next users page
     * if no next page return empty collection
     *
     * @return UserCollection
     */
    public function getNextUsers(): UserCollection
    {
        $nextOptions = $this->getNextOptions();
        if (empty($nextOptions)) {
            return new UserCollection();
        }
        return $this->getUsers(['query' => $nextOptions]);
    }

    public function getGroups(array $options = [])
    {
        return $this->dataStore->getCollection(
            '/api/v1/groups',
            Group::class,
            GroupCollection::class,
            $options
        );
    }

    public function getLogs(array $options = [])
    {
        return $this->dataStore->getCollection(
            '/api/v1/logs',
            LogEvent::class,
            LogCollection::class,
            $options
        );
    }
}
