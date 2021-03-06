<?php

namespace Zikula\Core\Forms\Renderer;

use Symfony\Component\Form\FormView;
use Zikula\Core\Forms\RendererInterface;
use Zikula\Core\Forms\FormRenderer;

/**
 *
 */
class FieldErrors implements RendererInterface
{
    public function getName()
    {
        return 'field_errors';
    }
    
    protected function getDivClassName() {
        return 'help-block';
    }

    public function render(FormView $form, $variables, FormRenderer $renderer)
    {
        $html = '';
        
        if ($variables['errors'] && !$this->getRoot($form)->get('omit_errors', false)) {
            $html .= '<div class="' . $this->getDivClassName() . ' alert alert-danger"><ul>';
            
            foreach ($variables['errors'] as $error) {
                $html .= '<li>' . $error->getMessageTemplate() . '</li>'; //TODO: $error->getMessageParameters()
            }
            
            $html .= '</ul></div>';
        }
        
        return $html;
    }
    
    private function getRoot(FormView $form) {
        while($form->getParent() != null) {
            $form = $form->getParent();
        }
        
        return $form;
    }
}
