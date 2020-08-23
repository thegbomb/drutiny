<?php

namespace Drutiny;

/**
 *
 */
class AssessmentManager implements AssessmentInterface {
    protected $assessments = [];

    public function addAssessment(Assessment $assessment)
    {
        $this->assessments[$assessment->uri()] = $assessment;
    }

    public function getAssessments():array
    {
        return $this->assessments;
    }

    public function getAssessmentByUri($uri):AssessmentInterface
    {
        return $this->assessments[$uri];
    }

    public function getResultsByPolicy($policy_name):array
    {
        $results = [];
        foreach ($this->assessments as $assessment) {
            $results[$assessment->uri()] = $assessment->getPolicyResult($policy_name);
        }
        return $results;
    }

    public function getPolicyNames()
    {
        $names = [];
        foreach ($this->assessments as $assessment) {
            foreach (array_keys($assessment->getResults()) as $name) {
              $names[$name] = $name;
            }
        }
        return array_values($names);
    }
}
