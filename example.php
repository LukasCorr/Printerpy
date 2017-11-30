<?php

/**
* Copyright (C) 2017 Lucas Correa.
*/

class pyPrinter
{

  function __construct($env)
  {
    $this->Connect($env);
  }

  public function receipt()
  {
    try {
      $ult = $this->ctrl->ConsultarUltNro($_POST['typeinvoice']);
      $nro = "{$ult}\n"+1;

      $tipo_cbte = $_POST['typeinvoice'];
      $tipo_doc = 80;                   // CUIT
      $nro_doc = $_POST['cuit'];
      $nombre_cliente = $_POST['razonsocial'];
      $domicilio_cliente = $_POST['domicilio'];
      $tipo_responsable = $_POST['conditioniva'];
      $referencia = "";                       // solo para NC / ND

      $this->ctrl->AbrirComprobante($tipo_cbte, $tipo_responsable, 
        $tipo_doc, $nro_doc,
        $nombre_cliente, $domicilio_cliente, 
        $referencia);            
      
      # Imprimo un art�culo:
      $ds = $_POST['description'];
      $qty = $_POST['quantity'];
      $price = $_POST['price'];
      $bonif = 0.00; 
      $alic_iva = $_POST['typeiva'];
      $type_payment = $_POST['type_payment'];
      $payment = $_POST['payment'];

      $this->ctrl->ImprimirItem($ds, $qty, $price, $alic_iva);

          # Imprimir un pago (si es superior al total, se imprime el vuelto):
      $this->ctrl->ImprimirPago($type_payment, $payment);

          # Finalizar el comprobante (imprime pie del comprobante, CF DGI, etc.) 
      $this->ctrl->CerrarComprobante();
      echo "<h2> Su comprobante N° ".$nro." se imprimió con éxito!</h2>";

    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }

  private function Connect($env)
  {
    error_reporting(-1);
    try {
      if (@class_exists('COM')) {
        $this->ctrl = new COM('PyFiscalPrinter') or die("No se puede crear el objeto");
        # habilitar excecpciones (capturarlas con un bloque try/except), ver abajo:
        $this->ctrl->LanzarExcepciones = true;
      } else if (@class_exists('Dbus')) {
          $dbus = new Dbus( Dbus::BUS_SESSION, true );
          $this->ctrl = $dbus->createProxy("ar.com.pyfiscalprinter.Service",  
                                          "/ar/com/pyfiscalprinter/Object",  
                                          "ar.com.pyfiscalprinter.Interface");
      } else {
        echo "No existe soporte para COM (Windows) o DBus (Linux) \n";
        exit(1);
      }

      $ok = $this->ctrl->Conectar($env['marca'], $env['modelo'], 
        $env['puerto'], $env['equipo']);

      if ($ok) {
        echo "<h4>Impresora '".$env['marca'].' '.$env['modelo']."' conectada!</h4>";
      } else {
        # Analizar errores (si no se habilito lanzar excepciones)
        echo "Excepcion: {$ctrl->Excepcion}\n";
        echo "Traza: {$ctrl->Traceback}\n";
        exit(1);
      }
    } catch(Exception $e) {
      echo $e->getMessage();
    }
  }

  public function closingZ() {
    try {
      if ($this->ctrl->CierreDiario('Z')) {
        echo "<h3>Se realizó un cierre Z!</h3>";
      }
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }

  public function closingX() {
    try {
      if($this->ctrl->CierreDiario('X')) {
        echo "<h3>Se realizó un cierre X!</h3>";
      }
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }
}

$env = parse_ini_file("env.ini");
$invoice = new pyPrinter($env);

if (isset($_GET['cierrez'])) {
  $invoice->closingZ();
} else if (isset($_GET['cierrex'])) {
  $invoice->closingX();
} else {
  $invoice->receipt();
}
echo "<a href='./'>Volver</a>";
?>