<?php
/**
 * Description of xml2java
 *
 * @author SolidSnake
 */
class xml2java {
    
    public $xmlFile;
    
    
    public function getAllMethods($url)
    {
        //fetch file
        $content = file_get_contents($url);
        
        //all methods array
        $allMethods = array();
        
        //functions refrence array
        $funcRefArr = array();
        
        
        //remove un-wanted tags
        $content = preg_replace("/<div[^>]*class=\"jd-tagdata jd-tagdescr\"[^>]*>.*?<\/div>/is", "", $content);
        
        
        //add all possible correct methods with correct params
        if (preg_match_all("/<div[^>]*class=\"jd-details api[^\"]*\"[^>]*>[^<]+<[^<]+<[^<]+<[^<]+<span[^>]*class=\"sympad\"[^>]*>([^<]+)<\/span>[^(]+\(([^)]+)\)<[^<]+<[^<]+<[^<]+<[^<]+<[^<]+<[^<]+<[^<]+<[^<]+<[^<]+<[^<]+<h5[^>]*class=\"jd-tagtitle\"[^>]*>Related XML Attributes<\/h5>\s*<ul[^>]*class=\"nolist\"[^>]*>(.*?)<\/ul>/is", $content, $records)) 
        {
            $records[2] = array_map(function($str) { return trim(strip_tags($str));}, $records[2]);
            $records[3] = array_map(function($str) { return preg_replace("/ /i", "", strip_tags(trim($str)));}, $records[3]);
                
            for($i=0; $i<count($records[1]); $i++)
            {
                //explode params + attributes
                $params = explode(", ", $records[2][$i]);
                $xmlAtts = explode("\n", $records[3][$i]);
                
                //$combined = @array_combine($xmlAtts, $params);
                $combined = array();
                
                if(count($params) == count($xmlAtts))
                {
                    $combined = array_combine($xmlAtts, $params);
                }
                else
                {
                    for($n=0; $n<count($params); $n++)
                    {
                        if(isset($xmlAtts[$n]))
                        {
                            $combined[$xmlAtts[$n]] = $params[$n];
                        } 
                    }
                }
                
                
                
                
                $xmlAttsStr = str_replace("\n", ",", $records[3][$i]);
                //$allMethods[] = $records[1][$i]."($xmlAttsStr)"; 
                
                $allMethods[$records[1][$i]][] = $combined;
               // $allMethods[$records[1][$i]][][] = $params;
                
                //refrence all java funcs to xml atts
                foreach($xmlAtts as $myAtt)
                    $funcRefArr[$myAtt] = $records[1][$i];
            }
            
           //$allMethods = array_unique($allMethods);
            
           //print_r($myArr);
                
        }
        
        
        
        
        
        /*//parse control attributes table
        if (preg_match_all("/<table[^>]*id=[\"']lattrs[\"'][^>]*class=[\"']jd-sumtable[\"'][^>]*>(.*?)<!--/is", $content, $table)) 
        {
            $funcTable = $table[0][0];
            
            //parse columns and rows
            if (preg_match_all("/<tr[^>]*class=\"[^>]*api apilevel-[^>]*\"[^>]*>\s*<td[^>]*jd-linkcol[^>]*>[^<]*<a[^<]*>\s*([^>]+)\s*<\/a>\s*<\/td>\s*<td[^>]*jd-linkcol[^>]*>[^<]*<a[^<]*>\s*([^>]+)\s*<\/a>\s*<\/td>/is", $funcTable, $matches)) 
            {  
                $myMethods = array_combine($matches[1], $matches[2]);
                
                //java functions
                foreach($myMethods as $xmlName => $javaName)
                { 
                    $javaName = preg_replace("/\([^>]+\)/", "", $javaName);
                    
                
                    $allMethods[] = "({$xmlName})";
                    
                }
                
                
                
                
               // print_r($allMethods);
                
                
            }  
        }
        
        
        //parse inharieted atts table
        if (preg_match_all("/<table[^>]*id=[\"']inhattrs[\"'][^>]*class=[\"']jd-sumtable[\"'][^>]*>(.*?)<\/table>/is", $content, $table)) 
        {
            $funcTable = $table[0][0];
            
            //parse columns and rows
            if (preg_match_all("/<tr[^>]*class=\"[^>]*api apilevel-[^>]*\"[^>]*>\s*<td[^>]*jd-linkcol[^>]*>[^<]*<a[^<]*>\s*([^>]+)\s*<\/a>\s*<\/td>\s*<td[^>]*jd-linkcol[^>]*>[^<]*<a[^<]*>\s*([^>]+)\s*<\/a>\s*<\/td>/is", $funcTable, $matches)) 
            {  
                $myMethods = array_combine($matches[1], $matches[2]);
                
                //java functions
                foreach($myMethods as $xmlName => $javaName)
                {
                    if(!isset($allMethods[$javaName]))
                    {
                        $allMethods[$javaName][] = $xmlName;
                    }
                    else
                    {
                        $allMethods[$javaName][] = $xmlName;
                    } 
                }
                
                
            }   
        }*/
                
        
        
        //print_r($allMethods);
                    
        return array($allMethods, $funcRefArr);
        
    }






    public function loadXML($XMLfile = null) {
        //invalid file
        if(!$XMLfile)
        {
            throw new InvalidArgumentException("Please provide XML file!");
        }
        
        //if file exists
        if(file_exists($XMLfile))
        {
             //load the file and push it to a variable
            $this->xmlFile = simplexml_load_file($XMLfile);
                
        }
        else 
        {
            echo "File doesn't exist!";
        }
     
    }
    
    
    public function parseAllElements() {
         
        //echo $this->xmlFile->getName();
                
        //filter child elements
        foreach($this->xmlFile->children() as $child)
        { 
            switch ($child->getName())
            {
                case "TextView":
                
                    $excludedMethods = array ("setId(int)");
                    
                    
                    //returns all of the methods from android documentation page
                    $tmpMethods = $this->getAllMethods("http://developer.android.com/reference/android/widget/" . $child->getName(). ".html");
                    $allJavaMethods = $tmpMethods[0];
                    $funcRefMethods = $tmpMethods[1];

                    //returns an array of attributes of the current item
                    $attArray = $child->attributes("android", 1); 
                    
                    
                    //set variable name (such as: TextView blabla;)
                    $controlVariable = $this->setControlVariable($attArray["id"]);
                
                    
                    //array contains all attributes and values
                    $allAttributes = array();
                    
                    foreach ($attArray as $attrName => $attrValue)
                    {
                        $allAttributes["android:".$attrName] = (string) $attrValue; 
                    }
                    //-----------------------------------------------------------
                
                
                    //find and replace methods
                    foreach($allAttributes as $attrName => $attrValue)
                    {  
                        //search for the equivilant method for the current attribute
                        if(isset($funcRefMethods[$attrName]))
                        {
                            //get java func name from refmethods array
                            $refFunction = $funcRefMethods[$attrName];
                            
                            //get list of xml related atts to java methods
                            $currentMethod = $allJavaMethods[$refFunction];
                
                            
                            //loop through all overloaded methods
                            foreach($currentMethod as $myMethodAttributes)
                            { 
                                $methodNam[$refFunction] = array();
                
                                
                                //loop over all attributes and fill them with values + remove related atts from original array
                                foreach($myMethodAttributes as $myAttribute => $myParam)
                                { 
                                    //get value from original table
                                    if(isset($allAttributes[$myAttribute]))
                                        $methodNam[$refFunction][] = $this->repairedValue($allAttributes[$myAttribute]."::$myParam");
                                    else
                                        $methodNam[$refFunction][] = "$myParam";
                                    
                                   // unset($allAttributes[$myAttribute]);
                                    
                                }
                
                            }
                
                        }
                
                    }
                    
                    
                    //print contol and its all methods
                    echo $this->printControlMethods($methodNam, $controlVariable, $child->getName());
                    
                    
                   // print_r($methodNam);
                


                break;
            }
            
                

        }
        
    }
    
    
 
    
    //repair value and return it back
    public function repairedValue($params)
    {
        $values = explode("::", $params);
        $paramType = preg_replace("/ .*/is", "", $values[1]);
        $value = $values[0];
        
        
        
        //echo "$paramType\n";
        switch ($paramType)
        { 
            case "CharSequence":
                return "\"".str_replace("\"", "\"\"", $value)."\"";
                break;
            
            case "int" || "float" || "boolean":
            
                if(preg_match('/#[0-9a-f]{6,8}/i', $value)) //if html color
                {
                    return "Color.parseColor(\"$value\")";
                }
                else if(preg_match('/[0-9]+sp/i', $value)) //if html color
                {
                    return "(".str_replace("sp", "", $value)."/context.getResources().getDisplayMetrics().scaledDensity)";
                }
                else if(preg_match("/^[a-z]+$/im", $value))
                {
                    return strtoupper($value);
                }
                else 
                {
                    return $value;
                }
                
                break;
            
            default :
                return $value;
                break;
        }
        
        
    }
    
    
    //print control methods, definitions etc (control array, control variable name, control type)
    public function printControlMethods($controlArr, $controlVarName, $controlType)
    {
        
        switch ($controlType)
        {
            case "TextView":
                $controlDefinitionName = "TextView {$controlVarName} = new TextView();\r\n<br />"; 
                break;
            
            default :
                $controlDefinitionName = "View {$controlVarName} = new TextView();\r\n<br />";
                break;
        }
        
        
        
        $functionsNames = "";
        foreach($controlArr as $funcName => $parameters)
        {
            
            $functionsNames .= $controlVarName.".".$funcName."(". implode(", ", $parameters).");\r\n<br />";
            
        }
        
        
        $allData = $controlDefinitionName.$functionsNames;
        return $allData;
    }
    
    
    //set control variable function
    public function setControlVariable($idString = null)
    {
        if(!empty($idString) && stripos($idString,"/"))
        {
            return explode("/",$idString)[1];
        }
        else 
        { 
            return "tv".rand(1, 1000);
        }
    }
    
}

?>