<?php
use App\Models\Aula;
$aulas=['A1','A2','A3','A4','B1','B2','B3','B4','LAB FINANZAS','LAB 1','C1','C2','C3','E1','E2','E3','E4','E5','E6','E7','E8'];
foreach($aulas as $a){
    Aula::firstOrCreate(['nombre'=>$a],['capacidad'=>40]);
}
echo "Aulas creadas con exito.";
