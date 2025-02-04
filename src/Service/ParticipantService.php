<?php

namespace App\Service;

use App\Entity\EmailAddress;
use App\Entity\Participant;
use App\Entity\Party;
use App\Validator\ParticipantIsNotBlacklisted;
use Doctrine\ORM\EntityManagerInterface;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ParticipantService
{
    public function __construct(
        private EntityManagerInterface $em,
        public ParticipantShuffler $participantShuffler,
        private ValidatorInterface $validator,
        private string $geoIpDbPath,
    ) {
    }

    /**
     * Shuffles all participants for party and save result to each participant.
     */
    public function shuffleParticipants(Party $party): bool
    {
        // Validator should already have shuffled it.
        if (!$shuffled = $this->participantShuffler->shuffleParticipants($party)) {
            return false;
        }

        foreach ($party->getParticipants() as $key => $participant) {
            $match = $shuffled[$key];
            $participant->setAssignedParticipant($match);

            $this->em->persist($participant);
        }

        $this->em->flush();

        return true;
    }

    public function validateEmail(string $email): bool
    {
        $emailAddress = new EmailAddress($email);

        $emailAddressErrors = $this->validator->validate($emailAddress);
        $blacklisted = $this->validator->validate($emailAddress, new ParticipantIsNotBlacklisted());
        if ((count($emailAddressErrors) > 0 || count($blacklisted)) > 0) {
            return false;
        }

        return true;
    }

    public function editParticipant(Participant $participant, string $name, string $email): void
    {
        $participant->setEmail($email);
        $participant->setName($name);

        $this->em->persist($participant);
        $this->em->flush();
    }

    public function logFirstAccess(Participant $participant, string $ip): void
    {
        if (null === $participant->getViewdate()) {
            $participant->setViewdate(new \DateTime());

            $this->em->flush();
        }

        if (null === $participant->getIp()) {
            $participant->setIp($ip);

            $reader = new Reader($this->geoIpDbPath);

            try {
                $geoInformation = $reader->city($participant->getIp());

                $participant->setGeoCountry($geoInformation->country->isoCode);
                $participant->setGeoProvince($geoInformation->mostSpecificSubdivision->isoCode);
                $participant->setGeoCity($geoInformation->city->name);
            } catch (AddressNotFoundException $ex) {
                $participant->setGeoCountry('');
            }

            if (empty($participant->getGeoCountry())) {
                $participant->setGeoCountry('');
                $participant->setGeoProvince('');
                $participant->setGeoCity('');
            }

            $this->em->flush();
        }
    }
}
