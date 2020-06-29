<?php

namespace Drutiny\Report\Format;

use Drutiny\Profile;
use Drutiny\Report\Format;
use Drutiny\AssessmentInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

class JSON extends Format
{
    protected $format = 'json';
    protected $extension = 'json';
    protected $data;

    protected function prepareContent(Profile $profile, AssessmentInterface $assessment)
    {
        $json = [
          'date' => date('Y-m-d'),
          'human_date' => date('F jS, Y'),
          'time' => date('h:ia'),
          'uri' => $assessment->uri(),
        ];
        $json['profile'] = $profile->export();
        $json['reporting_period_start'] = $profile->getReportingPeriodStart()->format('Y-m-d H:i:s e');
        $json['reporting_period_end'] = $profile->getReportingPeriodEnd()->format('Y-m-d H:i:s e');
        $json['policy'] = [];
        $json['results'] = [];
        $json['totals'] = [];

        foreach ($assessment->getResults() as $response) {
          $policy = $response->getPolicy();
          $json['policy'][] = $policy->export();

          $result = $response->export();
          $result['policy'] = $policy->name;
          $json['results'][] = $result;

          $total = $json['totals'][$response->getType()] ?? 0;
          $json['totals'][$response->getType()] = $total+1;
        }

        $json['total'] = array_sum($json['totals']);

        $this->data = $json;
        return $this->data;
    }

    public function render(Profile $profile, AssessmentInterface $assessment)
    {
        $this->buffer->write(json_encode($this->prepareContent($profile, $assessment)));
        return $this;
    }
}
