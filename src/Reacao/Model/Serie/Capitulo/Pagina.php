<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Reacao\Model\Serie\Capitulo;

use Broda\Model\AbstractModel;
use Doctrine\ORM\Mapping as ORM;
use Reacao\Model\Serie\Capitulo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Description of Pagina
 *
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 *
 * @ORM\Entity
 * @ORM\EntityListeners({"Reacao\Listener\Model\Serie\Capitulo\PaginaListener"})
 */
class Pagina extends AbstractModel
{

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64, unique=true)
     *
     * @Assert\NotBlank
     */
    protected $filename;

    /**
     * Capitulo das paginas
     *
     * @var Capitulo
     *
     * @ORM\ManyToOne(targetEntity="Capitulo", inversedBy="paginas")
     * @ORM\JoinColumn(name="capitulo_id", referencedColumnName="id")
     *
     * @Assert\NotBlank
     */
    protected $capitulo;

    /**
     *
     * @var float
     *
     * @ORM\Column(type="float")
     */
    protected $order;

    /**
     *
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $html;

    /**
     * Se a pagina sera votavel ou nao.
     *
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $votable;

    /**
     * Se a pagina sera dupla ou nao.
     *
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $doublePage;

    /**
     * Arquivo a ser uploadeado na pagina
     *
     * @var File
     *
     * @Assert\Image(
     *     maxSize = 2000000,
     *     minWidth = 600,
     *     maxWidth = 2600,
     *     minHeight = 600,
     *     maxHeight = 2600,
     *
     *     maxSizeMessage = "Pesada pra caramba esta página, hein! Só vamos aceitar se ela pesar até {{ limit }} {{ suffix }}.",
     *     mimeTypesMessage = "Só aceitamos imagens. Outros arquivos? Passamos.",
     *     notFoundMessage = "Ué, cadê a imagem? Enviou mesmo?",
     *     notReadableMessage = "Nosso servidor jura que fez o fundamental, mas ele não conseguiu ler o arquivo! (not readable)",
     *     uploadErrorMessage = "Alguma coisa errada, isso geralmente não acontece... Tenta de novo, por favor.",
     *     minWidthMessage = "Hmm.. quase não dá pra enxergar essa página de tão pequena! Faça o favor e envie páginas mais legíveis, ok?",
     *     minHeightMessage = "Hmm.. quase não dá pra enxergar essa página de tão pequena! Faça o favor e envie páginas mais legíveis, ok?",
     *     maxWidthMessage = "Muito grande essa imagem! Só aceitamos até {{ width }}px de largura.",
     *     maxHeightMessage = "Muito grande essa imagem! Só aceitamos até {{ height }}px de altura."
     * )
     */
    private $file;

    /**
     * Guarda num cache o último arquivo salvo no banco, para depois
     * de alterar a página, deletar a antiga imagem.
     *
     * @var string
     */
    private $tempFilename;

    /**
     * Cache do diretório final onde as páginas serão salvas
     *
     * @var string
     */
    private $folder;

    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    public function setFile(File $file)
    {
        $this->file = $file;
        // check if we have an old image path
        if (isset($this->filename)) {
            // store the old name to delete after the update
            $this->tempFilename = $this->filename;
            $this->filename = null; // para registar a mudança
        } else {
            $this->filename = 'initial'; // para registar a mudança
        }
    }

    public function unsetFile()
    {
        $this->file = null;
    }

    public function getOldFilename()
    {
        return $this->tempFilename;
    }

    public function unsetOldFilename()
    {
        $this->tempFilename = null;
    }

    public function getFolder()
    {
        if (isset($this->folder)) {
            return $this->folder; // cache
        }
        // a pasta onde serão salvo as imagens será dentro da pasta
        // do autor principal da serie
        return $this->folder = $this->capitulo->getFolder();
    }

    public function getAbsolutePath()
    {
        if (null !== $this->filename) {
            return $this->getFolder() . '/' . $this->filename;
        }
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    public function getCapitulo()
    {
        return $this->capitulo;
    }

    public function setCapitulo(Capitulo $capitulo)
    {
        $this->capitulo = $capitulo;
    }

    public function getSerie()
    {
        return $this->capitulo->getSerie();
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder($order)
    {
        $this->order = (float)$order;
    }

    public function isVotable()
    {
        return $this->votable;
    }

    public function setVotable($votable)
    {
        $this->votable = $votable;
    }

    public function isDoublePage()
    {
        return $this->doublePage;
    }

    public function setDoublePage($doublePage)
    {
        $this->doublePage = $doublePage;
    }

    public function getHtml()
    {
        return $this->html;
    }

    public function setHtml($html)
    {
        $this->html = $html;
    }

}
