<?php
namespace Pocs\Form;


class PocsForm
{
    protected $formFactory;

    public function __construct($formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * Login form.
     */
    public function getLoginForm($last_email = null)
    {
        return $this->formFactory->createBuilder('form')
            ->add('email', 'email',
                array('label' => 'Email',
                    'data' => $last_email,
                )
            )
            ->add('password', 'password', array('label' => 'Password'))
            ->getForm();
    }

    /**
     * Create user form.
     */
    public function getCreateForm($last_email = null)
    {
        return $this->formFactory->createBuilder('form')
            ->add('email', 'text',
                array('label' => 'Email',
                    'data' => $last_email,
                )
            )
            ->add('password', 'password', array('label' => 'Password'))
            ->add('conf_password', 'password', array('label' => 'Confirm password'))
            ->getForm();
    }

    /**
     * Frontend form.
     */
    public function getAddFrontendForm()
    {
        return $this->formFactory->createBuilder('form')
            ->add('url', 'text', array('label' => 'Url'))
            ->add('name', 'text', array('label' => 'Name'))
            ->getForm();
    }
}

