<?php

namespace StatisticBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Irev\MainBundle\Document\User;
use Irev\MainBundle\ODM\Aggregator;
use Psr\Log\LoggerInterface;
use StatisticBundle\Document\StatisticsUsers;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersCollectCommand extends Command
{
    /**
     * @var DocumentManager
     */
    protected $dm;

    protected static $defaultName = 'statistic:users:collect';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(DocumentManager $dm, LoggerInterface $logger)
    {
        parent::__construct();

        $this->dm = $dm;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Import users statistics by dates')
            ->addArgument('date', InputArgument::OPTIONAL, 'Import date', 'yesterday')
            ->addOption('period', InputOption::VALUE_OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = new \DateTime($input->getArgument('date'));
        $date->setTime(0, 0, 0);

        $dates = [];
        if ($input->getOption('period')) {
            $to = new \DateTime('yesterday');

            while ($date <= $to) {
                $dates[$date->format('Ymd')] = clone $date;
                $date->add(new \DateInterval('P1D'));
            }
            sort($dates);
        } else {
            $dates = [$date];
        }

        foreach ($dates as $date) {
            $this->collectByDate($date);
        }
    }

    private function collectByDate(\DateTime $date)
    {
        $dateStart = clone $date;
        $dateEnd = clone $date;

        $dateStart->setTime(0, 0, 0);
        $dateEnd->setTime(23, 59, 59);

        $this->logger->info(sprintf('Collect users statistics by %s', $date->format('Y-m-d')));

        $aggregator = new Aggregator($this->dm, User::class);

        $pipeline = [
            ['$facet' => [
                'by_created' => [
                    ['$match' => [
                        'createdAt' => [
                            '$gte' => new \MongoDate($dateStart->getTimestamp()),
                            '$lte' => new \MongoDate($dateEnd->getTimestamp()),
                        ]
                    ]],
                    ['$group' => [
                        '_id' => null,
                        'count' => ['$sum' => 1]
                    ]]
                ],
                'complete_registration' => [
                    ['$match' => [
                        'completeRegistration' => true,
                        'completeRegistrationDate' => [
                            '$gte' => new \MongoDate($dateStart->getTimestamp()),
                            '$lte' => new \MongoDate($dateEnd->getTimestamp()),
                        ]
                    ]],
                    ['$group' => [
                        '_id' => null,
                        'count' => ['$sum' => 1]
                    ]]
                ]
            ]]
        ];

        $result = $aggregator->aggregate($pipeline)[0];

        $statisticsRepository = $this->dm->getRepository(StatisticsUsers::class);
        $data = $statisticsRepository->findOneBy(['date' => $date]);
        if (!$data) {
            $data = new StatisticsUsers();
        }

        $data
            ->setDate($date)
            ->getUsers()
                ->setCompleteRegistration($result['complete_registration'][0]['count'])
                ->setImportedOldAccount(0)
                ->setNew($result['by_created'][0]['count'])
        ;

        $this->dm->persist($data);
        $this->dm->flush();
    }
}
