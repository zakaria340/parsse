<?php
// src/AppBundle/Entity/Document.php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

class Document
{
    /**
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank(message="Please, upload the document as a PDF file.")
     * @Assert\File(mimeTypes={ "application/pdf" })
     */
    private $pdf;

    public function getPdf()
    {
        return $this->pdf;
    }

    public function setPdf($pdf){
        $this->pdf = $pdf;
        return $this;
    }
}
