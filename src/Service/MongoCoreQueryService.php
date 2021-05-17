<?php

namespace App\Service;


use Symfony\Component\DependencyInjection\ContainerInterface;

class MongoCoreQueryService
{

    public $container;
    public $mongoClientUrl;
    public $mongoDatabaseName;

    public function __construct(ContainerInterface $container)
    {
        $this->container         = $container;
        $this->mongoClientUrl    = $container->getParameter('mongo_client_url');
        $this->mongoDatabaseName = $container->getParameter('mongo_database_name');
    }

    public function profileViewCountTotal($userId, $timeAgo)
    {
        $mongoDatabaseName  = $this->mongoDatabaseName;
        $client             = new \MongoDB\Client($this->mongoClientUrl);
        $collection         = $client->$mongoDatabaseName->ProfileView;
        $total              = 0;
        $options            = [];

        $pipeline = [
            [
                '$match' => [
                    'user_id' => [
                        '$eq' => $userId
                    ],
                    'unique' => [
                        '$eq' => FALSE
                    ],
                    'date' => [
                        '$gte' => date('Y-m-d', strtotime($timeAgo)),
                        '$lte' => date('Y-m-d')
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => '$user_id',
                    'total' => [
                        '$sum' => '$count'
                    ]
                ]
            ]
        ];
        $cursor = $collection->aggregate($pipeline, $options);

        foreach ($cursor as $key => $document) {
            if($key == 0) {
                $total = $document['total'];
            }
        }

        return $total;
    }

    public function audioPlayCountTotal($userId, $timeAgo)
    {
        $mongoDatabaseName = $this->mongoDatabaseName;
        $client            = new \MongoDB\Client($this->mongoClientUrl);
        $collection        = $client->$mongoDatabaseName->AudioPlay;
        $total    = 0;
        $options  = [];
        $pipeline = [
            [
                '$match' => [
                    'user_id' => [
                        '$eq' => $userId
                    ],
                    'date' => [
                        '$gte' => date('Y-m-d', strtotime($timeAgo)),
                        '$lte' => date('Y-m-d')
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => '$user_id',
                    'total' => [
                        '$sum' => '$count'
                    ]
                ]
            ]
        ];
        $cursor = $collection->aggregate($pipeline, $options);

        foreach ($cursor as $key => $document) {
            if($key == 0) {
                $total = $document['total'];
            }
        }

        return $total;
    }

    public function audioLikesCount($userId, $timeAgo)
    {
        $mongoDatabaseName = $this->mongoDatabaseName;
        $client            = new \MongoDB\Client($this->mongoClientUrl);
        $collection        = $client->$mongoDatabaseName->AudioLike;
        $total    = 0;
        $options  = [];
        $pipeline = [
            [
                '$match' => [
                    'user_id' => [
                        '$eq' => $userId
                    ],
                    'date' => [
                        '$gte' => date('Y-m-d', strtotime($timeAgo)),
                        '$lte' => date('Y-m-d')
                    ]
                ]
            ]
        ];
        $cursor = $collection->aggregate($pipeline, $options);

        foreach ($cursor as $key => $document) {
            $total++;
        }

        return $total;
    }
}