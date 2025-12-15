return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Migrations\DoctrineMigrationsBundle::class => ['all' => true],

    // Dein Modul
    PulsR\SportabzeichenBundle\SportabzeichenBundle::class => ['all' => true],
];
