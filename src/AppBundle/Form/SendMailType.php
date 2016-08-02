<?php
// src/AppBundle/Form/ProductType.php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class SendMailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
          'subject',
          TextType::class,
          ['label' => 'Subject']
        );
        $builder->add(
          'body',
          TextareaType::class,
          array(
            'attr' => array('class' => 'tinymce'),
          )
        );
        $builder->add(
          'save',
          SubmitType::class,
          ['label' => 'Send']
        );
    }
}
