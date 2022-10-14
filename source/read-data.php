<?php

error_reporting(0);
include "php_serial.class.php";

$outputImage = '';

function drawImage ($pixels)
{
    global $outputImage;
    $limit_x = 0;
    $limit_y = 0;

    for ($a=0; $a<count($pixels); $a++)
    {
        $itens = trim($pixels[$a]);
        if (strlen($itens) > 0)
        {
            $limit_x++;
            $limit_y = 0;
            $itens = explode(',', $itens);

            for ($b=0; $b<count($itens); $b++)
            {
                $pixel = trim($itens[$b]);

                if (strlen($pixel) > 0)
                {
                    $limit_y++;
                }
            }
        }
    }

    echo 'x: '. $limit_x ."\n";
    echo 'y: '. $limit_y ."\n";

    $gd = imagecreatetruecolor($limit_x, $limit_y);

    for ($a=0; $a<count($pixels); $a++)
    {
        $itens = trim($pixels[$a]);
        if (strlen($itens) > 0)
        {
            $limit_x++;
            $limit_y = 0;
            $itens = explode(',', $itens);

            for ($b=0; $b<count($itens); $b++)
            {
                $pixel = trim($itens[$b]);

                if (strlen($pixel) > 0)
                {
                    $gray = imagecolorallocate($gd, $pixel, $pixel, $pixel);
                    imagesetpixel($gd, $a, $b, $gray);
                }
            }
        }
    }

    imagepng($gd, 'grayscale-1-'. $outputImage .'.png');
}

$outputImage = $_SERVER['argv'][1];

$serial = new phpSerial;
$serial->deviceSet("/dev/ttyACM0");
$serial->confBaudRate(115200);
$serial->deviceOpen();
$data = "";
$pixels = array();
$started = false;

$lineLimit = 256;
$incrementLimit = 1;

$lineBuffer = "";
$lineCounter = 0;
$lineCmdSended = false;
$lineCmdResponse = false;
$lineBlocks = array();

while (1)
{
    if ($lineCounter >= $lineLimit)
    {
        echo "Leitura das linhas completada!\n";
        break;
    }

    $read = $serial->readPort();
    $read = trim($read);
    $data .= $read;

    echo $read;

    // Inicia comunicação
    if ($started === false)
    {
        if (strstr($data, 'Jesus') && strstr($data, 'Amor'))
        {
            $started = true;
            echo "\nStarted!\n";
        }
        sleep(1);
    }

    // Comunicação já iniciada.
    else
    {
        // Envia comando para ler uma linha da matriz de pixel.
        if ($lineCmdSended === false && $lineCmdResponse === false)
        {
            $lineCmdSended = true;
            $lineBuffer = "";
            $serial->sendMessage('read:'. $lineCounter ."-". $outputImage ."\n");

            echo "\nSended command 1: read:". $lineCounter ."\n";
            sleep(3);
        }

        // Comando já enviado, processa resposta da linha lida.
        // Se tudo tiver ok, sinaliza e passa para próxima linha.
        else if ($lineCmdSended === true && $lineCmdResponse === false)
        {
            $lineBuffer .= $read;

            // Verifica se o buffer está OK.
            // Verifica se iniciou recebimento da linha, caso não,
            // envia comando para forçar início do envio.
            if (!strstr($lineBuffer, 'startline'))
            {
                $lineCmdSended = true;
                $lineBuffer = "";
                $serial->sendMessage('read:'. $lineCounter ."-". $outputImage ."\n"); 
                sleep(3);
            }

            else if (strstr($lineBuffer, 'startline') && strstr($lineBuffer, 'endline'))
            {
                $validBuffer = true;

                $lineData = explode('startline', $lineBuffer);
                $lineData = explode('endline', $lineData[1]);
                $lineData = trim($lineData[0]);
                
                for ($a=0; $a<strlen($lineData); $a++)
                {
                    switch ($lineData[$a])
                    {
                        case '0':
                        case '1':
                        case '2':
                        case '3':
                        case '4':
                        case '5':
                        case '6':
                        case '7':
                        case '8':
                        case '9':
                        case ',': break;
                        default: 
                            $validBuffer = false;
                    }

                    if ($validBuffer === false)
                        break;
                }

                // Buffer inválido, solicita scaneamento da linha novamente.
                if ($validBuffer === false)
                {
                    echo "\n\nLinha ". $lineCounter ." inválida, envia comanda de re-leitura.\n";
                    $lineBuffer = "";
                    $serial->sendMessage('read:'. $lineCounter ."-". $outputImage ."\n");
                    sleep(3);
                }

                // Linha válida! Salva informações e passa para 
                // próxima linha a ser escaneada.
                else
                {
                    echo "Linha ". $lineCounter ." válida!\n";

                    $lineBlock = explode('startline', $lineBuffer);
                    $lineBlock = explode(',endline', $lineBlock[1]);
                    $lineBlock = trim($lineBlock[0]);

                    $lineBlocks []= $lineBlock;
                    $lineCounter+=$incrementLimit;

                    // Reseta.
                    echo "\n\nFaz leitura de próxima linha: ". $lineCounter .".\n";
                    $lineBuffer = "";
                    $serial->sendMessage('read:'. $lineCounter ."-". $outputImage ."\n");
                    sleep(3);

                    // Renderiza blocos de pixels existentes
                    // para visualização em tempo real.
                    drawImage($lineBlocks);
                    drawImage2($lineBlocks);
                }
            }

            // Verifica se tem buffer inválido enquanto recebe dados.
            else
            {
                $validBuffer = true;

                $lineData = explode('startline', $lineBuffer);
                $lineData = explode('endline', $lineData[1]);
                $lineData = trim($lineData[0]);
                
                for ($a=0; $a<strlen($lineData); $a++)
                {
                    switch ($lineData[$a])
                    {
                        case '0':
                        case '1':
                        case '2':
                        case '3':
                        case '4':
                        case '5':
                        case '6':
                        case '7':
                        case '8':
                        case '9':
                        case ',': break;
                        default: 
                            $validBuffer = false;
                    }

                    if ($validBuffer === false)
                        break;
                }

                // Buffer inválido, solicita scaneamento da linha novamente.
                if ($validBuffer === false)
                {
                    echo "\n\nLinha ". $lineCounter ." inválida, envia comanda de re-leitura.\n";
                    $lineBuffer = "";
                    $serial->sendMessage('read:'. $lineCounter ."-". $outputImage ."\n");
                    sleep(3);
                }
            }
        }
    }
}

$serial->deviceClose();
echo "Finaliza comunicação.\n";

drawImage($lineBlocks);


