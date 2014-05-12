<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Reacao\Listener\Model\Serie\Capitulo;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Reacao\Model\Serie\Capitulo\Pagina;
use Reacao\Util\FileUtils;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Classe PaginaListener
 *
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 */
class PaginaListener
{

    /**
     * Ocorre antes da página ser salva/inserida no banco.
     *
     * Verifica se for passado algum arquivo e cria um nome único pra ele.
     *
     * @param             Pagina $pag   Pagina a ser alterada
     * @param LifecycleEventArgs $event Objeto do evento
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preUpload(Pagina $pag, LifecycleEventArgs $event)
    {
        if (null !== $file = $pag->getFile()) {
            // do whatever you want to generate a unique name
            $filename = FileUtils::generateFilename($file->guessExtension(), $pag->getFolder());
            $pag->setFilename($filename);
        }
    }

    /**
     * Ocorre após da página ser salva/inserida no banco.
     *
     * Pega o arquivo uploadeado e joga para a pasta correta, além de excluir
     * o arquivo anterior, caso seja um update.
     *
     * @param             Pagina $pag   Pagina a ser alterada
     * @param LifecycleEventArgs $event Objeto do evento
     *
     * @throws FileException Se o arquivo não puder ser movido
     *
     * @ORM\PostPersist
     * @ORM\PostUpdate
     */
    public function upload(Pagina $pag, LifecycleEventArgs $event)
    {
        if (null === $pag->getFile()) {
            return;
        }

        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $pag->getFile()->move($pag->getFolder(), $pag->getFilename());

        // check if we have an old image
        $tmp = $pag->getOldFilename();
        if (isset($tmp)) {
            // delete the old image
            unlink($pag->getFolder() . '/' . $tmp);
            // clear the temp image path
            $pag->unsetOldFilename();
        }
        $pag->unsetFile();
    }

    /**
     * Ocorre após da página ser deletada no banco.
     *
     * Verifica se for passado algum arquivo e cria um nome único pra ele.
     *
     * @param             Pagina $pag   Pagina a ser excluida
     * @param LifecycleEventArgs $event Objeto do evento
     *
     * @ORM\PostRemove
     */
    public function removeUpload(Pagina $pag, LifecycleEventArgs $event)
    {
        if ($file = $pag->getAbsolutePath()) {
            unlink($file);
        }
    }

}
