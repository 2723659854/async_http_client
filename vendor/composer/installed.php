<?php return array(
    'root' => array(
        'name' => '__root__',
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'reference' => NULL,
        'type' => 'library',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        '__root__' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'reference' => NULL,
            'type' => 'library',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'psr/http-message' => array(
            'pretty_version' => '1.1',
            'version' => '1.1.0.0',
            'reference' => 'cb6ce4845ce34a8ad9e68117c10ee90a29919eba',
            'type' => 'library',
            'install_path' => __DIR__ . '/../psr/http-message',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'psr/http-message-implementation' => array(
            'dev_requirement' => false,
            'provided' => array(
                0 => '1.0',
            ),
        ),
        'workerman/psr7' => array(
            'pretty_version' => 'v1.4.6',
            'version' => '1.4.6.0',
            'reference' => 'e21521b72b5a891bd1a7a51376da12801863ce5b',
            'type' => 'library',
            'install_path' => __DIR__ . '/../workerman/psr7',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'workerman/workerman' => array(
            'pretty_version' => 'v4.1.13',
            'version' => '4.1.13.0',
            'reference' => '807780ff672775fcd08f89e573a2824e939021ce',
            'type' => 'library',
            'install_path' => __DIR__ . '/../workerman/workerman',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);