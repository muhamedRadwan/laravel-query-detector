<?php

namespace BeyondCode\QueryDetector\Outputs;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class Alert implements Output
{
    public function boot()
    {
        //
    }

    public function output(Collection $detectedQueries, Response $response)
    {
        if (stripos($response->headers->get('Content-Type'), 'text/html') !== 0 || $response->isRedirection()) {
            return;
        }

        $content = $response->getContent();

        $outputContent = $this->getOutputContent($detectedQueries);

        $pos = strripos($content, '</body>');

        if (false !== $pos) {
            $content = substr($content, 0, $pos) . $outputContent . substr($content, $pos);
        } else {
            $content = $content . $outputContent;
        }

        // Update the new content and reset the content length
        $response->setContent($content);

        $response->headers->remove('Content-Length');
    }

    protected function getOutputContentOld(Collection $detectedQueries)
    {
        $output = '<script type="text/javascript">';
        $output .= "alert('Found the following N+1 queries in this request:\\n\\n";
        foreach ($detectedQueries as $detectedQuery) {
            $output .= "Model: ".addslashes($detectedQuery['model'])." => Relation: ".addslashes($detectedQuery['relation']);
            $output .= " - You should add \"with(\'".addslashes($detectedQuery['relation'])."\')\" to eager-load this relation.";
            $output .= "\\n";
        }
        $output .= "')";
        $output .= '</script>';

        return $output;
    }

    protected function getOutputContent(Collection $detectedQueries)
    {
        $output = '<script type="text/javascript">';
        $output .= "Swal.fire({title:'Found the following N+1 queries in this request:',html:'";
        foreach ($detectedQueries as $detectedQuery) {
            $output .= "Model: ".addslashes($detectedQuery['model'])." => Relation: ".addslashes($detectedQuery['relation']);
            $output .= " - You should add \"with(\'".addslashes($detectedQuery['relation']).
            "\')\" to eager-load this relation. in Line (".$detectedQuery['sources'][0]->line.") file name: ". addslashes( $detectedQuery['sources'][0]->name) ?? $detectedQuery['sources'][0]->name;
            $output .= " <br><br>";
        }
        $output .= "',width:800})";
        $output .= '</script>';

        return $output;
    }

}
