<?php
/**
 * Функция чтения матрицы из файла
 * @param $filename Имя файла с данными
 * @return array    Матрица данных
 */
function getMatrix($filename = "matrix.txt")
{
    $matrix = file_get_contents($filename);
    $matrix = explode("\n", $matrix);
    foreach($matrix as $key => $row)
        $matrix[$key] = explode("\t", trim($row));
    return $matrix;
}

/**
 * Функция возвращающая строку классов
 * @param $matrix   Матрица
 * @return array    Строка классов без дубликатов
 */
function getClasses($matrix)
{
    $classes = [];
    foreach($matrix as $row)
        $classes[] = end($row);
    $classes = array_unique($classes);
    return $classes;
}

/**
 * Функция расчета энтропии матрицы
 * @param $matrix   Матрицы
 * @return float    Значение энтропии
 */
function entropyT($matrix)
{
    $entropyT = 0.0;
    $classes = getClasses($matrix);
    $classNums = []; // Количество классов в строке

    foreach($matrix as $row) // Считает кол-во классов
        foreach($classes as $key => $el)
            if(end($row) == $el)
                $classNums[$key]++;

    foreach($classNums as $val)
        $entropyT -= (double)$val / count($matrix) * log($val / count($matrix));
    return $entropyT;
}

/**
 * Функция получения уникальных записей строки
 * @param $matrix
 * @param $rowNumber
 * @return array    Массив уникальных записей строк
 */
function getElements($matrix, $rowNumber)
{
    $rowElements = [];
    foreach($matrix as $row)
        $rowElements[] = $row[$rowNumber];
    $rowElements = array_unique($rowElements);
    return $rowElements;
}

/**
 * Функция получения числа повторений уникальных элементов
 * @param $matrix
 * @param $rowElements Строка|Число
 * @param $rowNumber
 * @return array    Количество повторений элементов в строке
 */
function getElemNums($matrix, $rowElements, $rowNumber)
{
    $rowElemNums = [];
    if((double) $rowElements[0] == 0) // Если строка
    {
        foreach($matrix as $row)
            foreach($rowElements as $key => $el)
                if($row[$rowNumber] == $el)
                    $rowElemNums[$key]++;
    } else // Если число
    {
        $allRowElements = [];
        foreach($matrix as $row)
            $allRowElements[] = (double) $row[$rowNumber];
        sort($allRowElements);
        array_pop($allRowElements); // исправить
        $z = (count($allRowElements) % 2) ? $allRowElements[count($allRowElements)/2] : $allRowElements[count($allRowElements)/2 - 1];
        foreach($matrix as $row)
            if((double)$row[$rowNumber] <= $z)
                $rowElemNums[0]++;
            else
                $rowElemNums[1]++;
    }

    return $rowElemNums;
}

/**
 * Функция получения числа элементов соотв. классов в каждой строке
 * @param $matrix
 * @param $rowElements Строка|Число
 * @param $classes
 * @param $rowNumber
 * @return array    Количество повторений элементов в строке в соотв с классами
 */
function getClassesInElements($matrix, $rowElements, $classes, $rowNumber)
{
    $classesInElements = [];
    foreach($rowElements as $eKey => $el)
        foreach($classes as $cKey => $class)
            $classesInElements[$eKey][$cKey] = 0;
    foreach($matrix as $key => $row)
    {
        $eCount = -1;
        $cCount = -1;
        foreach($rowElements as $eKey => $el)
            if($el == $row[$rowNumber])
                $eCount = $eKey;
        foreach($classes as $cKey => $class)
            if($class == $row[count($row) - 1])
                $cCount = $cKey;
        if($eCount == -1 || $cCount == -1) {
            var_dump("Error");
            die();
        }
        else
            $classesInElements[$eCount][$cCount]++;
    }
    return $classesInElements;
}

/**
 * Функция подсчета энтропии конкретной строки матрицы
 * @param $matrix       Исходная матрица
 * @param $rowNumber    Номер строки
 * @return float        Энтропия
 */
function entropyRow($matrix, $rowNumber)
{
    $entropy = 0.0;
    $rowSize = count($matrix);
    $rowElements = getElements($matrix, $rowNumber);
    $rowElemNums = getElemNums($matrix, $rowElements, $rowNumber);
    $classes = getClasses($matrix);
    $classesInElements = getClassesInElements($matrix, $rowElements, $classes, $rowNumber);
    foreach($rowElemNums as $eKey => $el)
        foreach($classes as $cKey => $class) {
            if($classesInElements[$eKey][$cKey] == 0)
                continue;
            else
                $entropy -= (double)$rowElemNums[$eKey] / $rowSize
                    * $classesInElements[$eKey][$cKey] / $rowElemNums[$eKey]
                    * log($classesInElements[$eKey][$cKey] / $rowElemNums[$eKey]);
        }
    return  $entropy;
}

/**
 * Функция подсчета энтропий
 * @param $matrix   Исходная матрица
 * @return array    Массив энтропий, без учета энтропии классов
 */
function entropy($matrix)
{
    $entropy = [];
    for($i = 0; $i < count($matrix[0]) - 1; $i++)
    {
        $entropy[$i] = entropyRow($matrix, $i);
    }
    return $entropy;
}

/**
 * Функция печати матрицы
 * @param $matrix       Матрица
 * @param string $name  Имя матрицы
 */
function printMatrix($matrix, $name = "Матрица")
{
    echo "<caption>$name</caption>";
    echo "<table>";
    foreach($matrix as $row)
    {
        echo "<tr>";
        foreach($row as $el)
            echo "<th>$el</th>";
        echo "</tr>";
    }
    echo "</table>";
}

/**
 * Функция возвращающая номер строки по которой раскладывают
 * @param $entropyT     Энтропия матрицы
 * @param $entropy      Энтропия строк
 * @return int|string   Номер строки победителя
 */
function decRowNum($entropyT, $entropy)
{
    $gain = [];
    foreach($entropy as $one)
        $gain[] = $entropyT - $one;
    $max = $gain[0];
    $i = 0;
    foreach($gain as $key => $one)
        if($one > $max)
        {
            $max = $one;
            $i = $key;
        }
    return $i;
}

/**
 * Функция раскладки строки
 * @param $row      строка
 * @param $rowNum   номер элемента
 * @return array    строка без элемента с номером
 */
function decompositionRow($row, $rowNum)
{
    $aRow = [];
    foreach($row as $key => $el)
        if($key != $rowNum)
            $aRow[] = $el;
    return $aRow;
}

/**
 * Функция раскладывающая матрицу
 * @param $matrix       матрица
 * @param $rowNumber    номер строки
 * @return array        массив новых матриц
 */
function decomposition($matrix, $rowNumber)
{
    $matrices = [[]];
    $rowElements = getElements($matrix, $rowNumber);
    $rowElemNums = getElemNums($matrix, $rowElements, $rowNumber);
    if((double) $rowElements[0] == 0) // Если строка
    {
        foreach($rowElements as $eKeys => $el)
            foreach($matrix as $row)
                if($row[$rowNumber] == $el)
                    $matrices[$eKeys][] = decompositionRow($row, $rowNumber);
    } else // Если число
    {
        $allRowElements = [];
        foreach($matrix as $row)
            $allRowElements[] = (double) $row[$rowNumber];
        sort($allRowElements);
        array_pop($allRowElements); // исправить
        $z = (count($allRowElements) % 2) ? $allRowElements[count($allRowElements)/2] : $allRowElements[count($allRowElements)/2 - 1];
        foreach($rowElements as $key => $el)
            $rowElements[$key] = (double) $el;
        foreach($matrix as $row)
        {
            if((double)$row[$rowNumber] <= $z)
                $matrices[0][] = decompositionRow($row, $rowNumber);
            else
                $matrices[1][] = decompositionRow($row, $rowNumber);
        }
        $rowElements[0] = "=< $z";
        $rowElements[1] = "> $z";
    }

    foreach($matrices as $key => $matrix)
        printMatrix($matrix, $rowElements[$key]);

    return $matrices;
}

/**
 * Старт программы
 */
$matrix = getMatrix();
$entropyT = entropyT($matrix);
$entropy = entropy($matrix);
var_dump($entropy);
$decRowNum = decRowNum($entropyT, $entropy);
printMatrix($matrix, "Исходная матрица");
$matrices = decomposition($matrix, $decRowNum);

foreach($matrices as $matrix)
{
    printMatrix($matrix, "Исходная матрица");
    $hasAnotherClasses = false;
    $classElement = end($matrix[0]);
    foreach($matrix as $row)
        if(end($row) != $classElement)
            $hasAnotherClasses = true;
    if(!$hasAnotherClasses)
        continue;
    $entropyT = entropyT($matrix);
    $entropy = entropy($matrix);
    var_dump($entropy);
    $decRowNum = decRowNum($entropyT, $entropy);
    $matrices = decomposition($matrix, $decRowNum);
}