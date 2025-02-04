<?php

namespace App\Service;

use App\Entity\Participant;
use App\Entity\Party;

class ParticipantShuffler
{
    public const SHUFFLE_TIME_LIMIT = 10; // seconds

    private $matchedExcludes;

    /**
     * @return array|bool
     */
    public function shuffleParticipants(Party $party)
    {
        if (isset($this->matchedExcludes[spl_object_hash($party)])) {
            return $this->matchedExcludes[spl_object_hash($party)];
        }

        return $this->shuffleTillMatch($party);
    }

    /**
     * @return array|bool
     */
    private function shuffleTillMatch(Party $party)
    {
        $timeToStop = microtime(true) + self::SHUFFLE_TIME_LIMIT;
        $participants = $party->getParticipants()->getValues();

        while (microtime(true) < $timeToStop) {
            $set = $this->shuffleArray($participants);
            if ($this->checkValidMatch($participants, $set)) {
                $this->matchedExcludes[spl_object_hash($party)] = $set;

                return $set;
            }
        }

        return false;
    }

    /**
     * @param Participant[] $participants
     * @param Participant[] $shuffled
     */
    private function checkValidMatch(array $participants, array $shuffled): bool
    {
        /** @var Participant[] $participants */
        foreach ($participants as $key => $participant) {
            $possibleMatch = $shuffled[$key];
            if ($participant === $possibleMatch || $participant->getExcludedParticipants()->contains($possibleMatch)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Participant[] $list
     */
    private function shuffleArray(array $list)
    {
        shuffle($list);

        return $list;
    }
}
