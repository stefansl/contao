<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Voter\DataContainer;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\FormFieldAccessVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class FormFieldAccessVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(5))
            ->method('isGranted')
            ->withConsecutive(
                [ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'form'],
                [ContaoCorePermissions::USER_CAN_EDIT_FORM, 42],
                [ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'form'],
                [ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'form'],
                [ContaoCorePermissions::USER_CAN_EDIT_FORM, 42],
            )
            ->willReturnOnConsecutiveCalls(true, true, false, true, false)
        ;

        $voter = new FormFieldAccessVoter($security);

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_form_field'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_form'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));
        $this->assertFalse($voter->supportsType(FormFieldAccessVoter::class));

        $token = $this->createMock(TokenInterface::class);

        // Unsupported attribute
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('tl_form_field', ['pid' => 42]),
                ['whatever'],
            ),
        );

        // Permission granted, so abstain! Our voters either deny or abstain,
        // they must never grant access (see #6201).
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('tl_form_field', ['pid' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_form_field'],
            ),
        );

        // Permission denied on back end module
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('tl_form_field', ['pid' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_form_field'],
            ),
        );

        // Permission denied on form
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('tl_form_field', ['pid' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_form_field'],
            ),
        );
    }

    public function testDeniesUpdateActionToNewParent(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(3))
            ->method('isGranted')
            ->withConsecutive(
                [ContaoCorePermissions::USER_CAN_ACCESS_MODULE],
                [ContaoCorePermissions::USER_CAN_EDIT_FORM, 42],
                [ContaoCorePermissions::USER_CAN_EDIT_FORM, 43],
            )
            ->willReturnOnConsecutiveCalls(true, true, false)
        ;

        $token = $this->createMock(TokenInterface::class);
        $voter = new FormFieldAccessVoter($security);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new UpdateAction('tl_form_field', ['pid' => 42], ['pid' => 43]),
                [ContaoCorePermissions::DC_PREFIX.'tl_form_field'],
            ),
        );
    }
}