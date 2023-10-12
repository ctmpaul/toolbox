<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\MovinetConversionType;
use Symfony\Component\HttpFoundation\Request;

class MovinetController extends AbstractController
{
    #[Route('/movinet', name: 'app_movinet')]
    public function index(): Response
    {
        return $this->render('movinet/index.html.twig', [
            'controller_name' => 'MovinetController',
        ]);
    }

    #[Route('/movinet/conversion', name: 'app_movinet_conversion')]
    public function conversion(Request $request): Response
    {
        $result = null;
        $form = $this->createForm(MovinetConversionType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $data = $form->getData();

            // ... perform some action, such as saving the task to the database

            if($data['json']){
                $submitted = $data['json'];
                $response = $this->arrayToFormat($this->fabLinesToArray(explode("\n", $submitted)), 'json');
            }
            elseif($data['kvar']){
                $submitted = $data['kvar'];
                $response = $this->arrayToFormat($this->fabLinesToArray(explode("\n", $submitted)), 'kvar');
            }

            $result = ['submitted' => $submitted, 'response' => $response];
            //var_dump($result);
        }

        return $this->render('movinet/conversion.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
        ]);
    }

    public function fabLinesToArray(array $fabLines){
        $arrayToJson = [
            'brand' => 'P',
            'customerKey' => 'ECLI',
            'items' => []
        ];

        foreach($fabLines as $key => $line){
            $explodedFab = explode('$', $line);
            $newItem = [
                'reference' => (string) $explodedFab[0],
                'qty' => 1,
                'options' => []
            ];
            foreach($explodedFab as $key => $option){
                if($key > 0){
                    $explodedOption = explode('=', $option);
                    $newItem['options'][] = [
                        'code' => (string) $explodedOption[0],
                        'value' => (string) $explodedOption[1]
                    ];
                }
            }
            $arrayToJson['items'][] = $newItem;
            
        }
        return $arrayToJson;
    }

    public function arrayToFormat(array $MOarray, $format = 'json'){
        $fabLinesArray = [];

        if($format === 'json'){
            return json_encode($MOarray, JSON_UNESCAPED_SLASHES);
        }
        elseif($format === 'kvar'){
            foreach ($MOarray['items'] as $item){
                $varArray = [];
                if(
                    isset($item['reference']) && isset($item['options']) && isset($item['qty']) &&
                    $item['reference'] && is_array($item['options']) &&
                    !empty($item['options']) && $item['qty'] > 0
                ){
                    $varArray['refint'] = $item['reference'];
                    $varArray['qte'] = $item['qty'];
                    $kvarArray = [];
                    foreach ($item['options'] as $variable){
                        $kvarArray[] = $variable['code'].$variable['value'];
                    }
                    $kvarString = implode('|', $kvarArray);
                    if($kvarString){
                        $varArray['k_var'] = '|'.$kvarString.'|';
                        $fabLinesArray[] = $varArray;
                    }
                }
            }
            return json_encode($fabLinesArray, JSON_UNESCAPED_SLASHES);;
        }

        return 'ERROR';
    }
}