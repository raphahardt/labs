<?php

namespace Broda\Component\Rest\Serializer\Construction;

use JMS\Serializer\Construction\UnserializeObjectConstructor;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\VisitorInterface;
use Pimple\Container;

/**
 * Esta classe representa o "construtor" dos objetos que são deserializados
 * pelo JSM\Serializer.
 *
 * Ela processa a string serializada e cria o objeto baseado no tipo
 * que você definiu no deserialize()
 *
 * Ex:
 * $dados = '{"nome":"joao", vivo:true}';
 * $obj = $serializer->deserialize($dados, 'Namespace\Classe', 'json');
 * // internamente, ele roda o ObjectConstructorInterface::construct() e
 * // este retorna um novo objeto da classe Namespace\Class
 *
 * $obj instanceof Namespace\Class; //TRUE
 */
class NaturalObjectConstructor extends UnserializeObjectConstructor
{
    public static function create(Container $app)
    {
        return new DoctrineObjectConstructor($app, new static());
    }

    public function construct(VisitorInterface $visitor, ClassMetadata $metadata, $data, array $type, DeserializationContext $context)
    {
        /* @var $reflection \ReflectionClass */
        $reflection = $metadata->reflection;
        $class = $metadata->name;

        $constructor = $reflection->getConstructor();

        if ($constructor) {
            $constructorParameters = $constructor->getParameters();

            $params = array();
            foreach ($constructorParameters as $constructorParameter) {
                $paramName = $constructorParameter->name;

                if (isset($data[$paramName])) {
                    $params[] = $data[$paramName];

                } elseif (!$constructorParameter->isOptional()) {
                    throw new \RuntimeException(
                        'Cannot create an instance of '.$class.
                        ' from serialized data because its constructor requires '.
                        'parameter "'.$constructorParameter->name.
                        '" to be present.');
                }
            }

            return $reflection->newInstanceArgs($params);
        }

        return parent::construct($visitor, $metadata, $data, $type, $context);
    }
}

