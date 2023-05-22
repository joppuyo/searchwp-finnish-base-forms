<?php

namespace NPX\FinnishBaseForms;

abstract class Lemmatizer {

    private $cache_array;
    public $__FILE__;

    abstract protected function lemmatize(string $word);

    public function __construct()
    {

    }

    public function parse_wordbases(array $wordbases)
    {
        $baseforms = [];
        foreach ($wordbases as $wordbase) {
            preg_match_all('/\(([^+].*?)\)/', $wordbase, $matches);
            foreach ($matches[1] as $match) {
                array_push($baseforms, str_replace('=', '', $match));
            }
        }
        return $baseforms;
    }

    function lemmatize_cached(string $word) {

        // Replace quotes

        // U+2019 kaareva heittomerkki (suomalaisen typografisen perinteen mukainen) &rsquo; &#8217; &#x2019;
        $word = str_replace('’', '\'', $word);

        // U+02B9 tarkkeenomainen yläpuolinen indeksointipilkku (kyrillistä kirjoitusta latinaistettaessa käytettävä
        // pehmeän merkin Ь vastine, jolla osoitetaan edeltävän kirjaimen liudennusta; käytössä myös heprean
        // latinaistuksessa tarkkeena tai välimerkkinä; joskus sanapainon merkkinä; käytännöllisesti katsoen
        // samannäköinen kuin luonnontieteellisissä yhteyksissä käytettävä yläpuolinen indeksointipilkku U+2032)
        // &#697; &#x2B9;
        $word = str_replace('ʹ', '\'', $word);

        // U+02BB arkkeenomainen ylösalainen pilkku (glottaaliklusiilina ääntyvä ʻokina-kirjain havaijin kielessä;
        // käytössä myös merkkien U+02BD ja U+02BF vaihtoehtoisena esitysmuotona; samannäköinen välimerkki on U+2018)
        // &#699 &#x2BB;
        $word = str_replace('ʻ', '\'', $word);

        // U+02BC tarkkeenomainen heittomerkki (käytössä eräissä kielissä toonimerkkinä tai glottaaliklusiilia tai
        // ejektiiviä osoittavana kirjaimena; samannäköinen kuin kaareva heittomerkki U+2019) &#700; &#x2BC;
        $word = str_replace('ʼ', '\'', $word);

        // U+02BD tarkkeenomainen käänteinen pilkku (heikkoa aspiraatiota osoittava kirjain joissain kielissä;
        // samannäköinen välimerkki on U+201B) &#701; &#x2BD;
        $word = str_replace('ʽ', '\'', $word);

        // U+02C8 tarkkeenomainen pystyviiva (kansainvälisen foneettisen kirjaimiston mukainen pääpainon merkki, joka
        // kirjoitetaan painollisen tavun alkuun; lähes samannäköinen kuin suora heittomerkki U+0027) &#712; &#x2C8;
        $word = str_replace('ˈ', '\'', $word);

        // U+02CA tarkkeenomainen akuutti-korkomerkki (nousevan toonin merkki; joskus sanapainon merkkinä;
        // samannäköinen kuin akuutti-korkomerkki U+00B4) &#714; &#x2CA;
        $word = str_replace('ˊ', '\'', $word);

        // U+02CB tarkkeenomainen gravis-korkomerkki (laskevan toonin merkki; joskus sanapainon merkkinä;
        // samannäköinen kuin gravis-korkomerkki U+0060) &#715; &#x2CB;
        $word = str_replace('ˋ', '\'', $word);

        // Replace dashes

        // U+2212 &minus; Unicoden miinusmerkki (minus)
        $word = str_replace('−', '-', $word);


        $word = preg_replace('/[^a-zA-Z0-9ŠšŽžÅåÄäÖö\-\']/u', ' ', $word);
        $word = trim($word);
        
        $key = 'swpfbf_' . md5($word);

        $lemmatized_data = get_transient($key);

        if ($lemmatized_data === false) {
            $lemmatized_data = $this->lemmatize($word);
            set_transient($key, $lemmatized_data, YEAR_IN_SECONDS);
        }
        
        return $lemmatized_data;
    }
}