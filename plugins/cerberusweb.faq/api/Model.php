<?php
class Model_Faq {
    public $id;
    public $question = '';
    public $is_answered = 0;
    public $answer = '';
    public $answered_by = 0;
    public $created;
    
    function getAnswer() {
        $answers = DAO_Faq::getAnswers(array($this->id));
        return $answers[$this->id];
    }
}
?>