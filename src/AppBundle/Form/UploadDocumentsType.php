<?php
// src/AppBundle/Form/ProductType.php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;


class UploadDocumentsType extends AbstractType {
  public function buildForm(FormBuilderInterface $builder, array $options) {
    $builder->add(
      'submitFile',
      FileType::class,
      ['label' => 'Document to Send']
    );
    $builder->add(
      'save',
      SubmitType::class,
      ['label' => 'Valider']
    );
  }
}
