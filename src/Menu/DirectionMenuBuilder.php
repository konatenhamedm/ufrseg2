<?php

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;

class DirectionMenuBuilder
{
    private $factory;
    private $security;
    /**
     * Undocumented variable
     *
     * @var \App\Entity\Utilisateur
     */
    private $user;

    private const MODULE_NAME = 'Direction';

    public function __construct(FactoryInterface $factory, Security $security)
    {
        $this->factory = $factory;
        $this->security = $security;
        $this->user = $security->getUser();
    }

    public function createMainMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $menu->setExtra('module', self::MODULE_NAME);
        if ($this->user->hasRoleOnModule(self::MODULE_NAME)) {
            $menu->addChild(self::MODULE_NAME, ['label' => 'Direction'])->setExtra('role', 'ROLE_SUPER_ADMIN');
        }

        if (isset($menu[self::MODULE_NAME])) {
            $menu->addChild('deliberation.index', ['route' => 'app_direction_deliberation_index', 'label' => 'Gestion des délibérations'])->setExtra('icon', 'bi bi-gear')->setExtra('role', 'ROLE_SUPER_ADMIN');
        }

        return $menu;
    }
}
