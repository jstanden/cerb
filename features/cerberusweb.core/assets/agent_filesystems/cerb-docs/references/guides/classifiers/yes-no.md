---
id: "guides-classifiers-yes-no"
title: "Build a yes/no classifier"
url: "https://cerb.ai/guides/classifiers/yes-no/"
summary: "This webpage provides a comprehensive guide on building a yes/no classifier using natural language processing to enhance automation in conversational bots. It explains the process of creating a classifier that can interpret user responses to questions like 'Are you sure?' and categorize them into 'yes,' 'no,' or 'maybe' intents. The guide covers steps such as creating a new classifier, importing and training with real-world examples, testing predictions, and continuing to refine the classifier for improved accuracy. It emphasizes the importance of using a diverse set of training data to avoid overfitting or underfitting and demonstrates how to adjust the classifier's predictions through additional training. The page also suggests potential next steps, such as integrating the classifier into bot behaviors or developing more complex classifiers for tasks like sentiment analysis or intent detection."
tags: ["guides"]
---
# Introduction

[Classifiers](/docs/classifiers/) take text written in natural language and learn to _classify_ it for you. [Automations](/docs/automations/) can use classifiers to make smarter decisions.

For instance, let's assume that we have a conversational bot who asks a question like _"Are you sure?"_ to confirm the user's intent before performing some action.

While we could force people to literally say _"yes"_ or _"no_" in response, they will have a much better experience if the bot attempts to understand them using natural language. It gets complicated. Even for such a simple question, there are hundreds of potential responses that a typical human could say to answer affirmatively or negatively.

What we really care about is whether the response indicates one of the following three intents:

- yes
- no
- maybe

That's exactly what a classifier does for us – it weighs the evidence it's given and makes a prediction. It's important to understand that classifiers can use their training to make accurate predictions even for responses they've never seen.

If a user responds to the bot with _"sure, make it happen!"_, the classifier may predict with 99.82% confidence that the user meant _"yes"_. If instead the user says _"stop, I don't want this"_, the classifier may predict with 82.97% confidence that they meant _"no"_.

With that information, an automation can pick the appropriate [outcome](/docs/automations/commands/decision/) for a _"Did they say yes?"_ decision.

In this guide, we'll quickly build the above classifier so that you can use it in your own bot behaviors.

- [Create a new classifier](#create-a-new-classifier)
- [Import training data](#import-training-data)
- [Test predictions](#test-predictions)
- [Continue training the classifier](#continue-training-the-classifier)
- [Next steps](#next-steps)

# Create a new classifier

Let's start by creating a new classifier.

Open a classifiers [worklist](/docs/worklists/) from **Search** » **Classifiers**.

Click the **(+)** icon above the the worklist.

 

Name the classifier _"Yes/No"_ and make _Cerb_ the owner. This will allow every bot to share it.

 

Click the **Save Changes** button.

# Import training data

Classifiers are trained with as many human-verified, real-world examples as you can provide. In the interest of time, we'll use the bulk import feature to add some training data. You can add additional training examples at any time.

Open the [card](/docs/cards/) for the newly created **Yes/No** classifier. As a shortcut, you can click its name in the yellow notification that appeared above the worklist when you added the record.

Once the popup opens, click the **Import** button at the top.

 

Copy the following training data to your clipboard:

```
answer.maybe,"I don't have a clue"
answer.maybe,"I don't have a clue"
answer.maybe,"I don't know"
answer.maybe,"I don't know"
answer.maybe,"I have no clue"
answer.maybe,"I have no idea"
answer.maybe,"I may agree"
answer.maybe,"I might agree"
answer.maybe,"I think so"
answer.maybe,"I think so"
answer.maybe,"I'm not sure if I agree"
answer.maybe,"I'm not sure if I agree"
answer.maybe,"I'm not sure if I agree"
answer.maybe,"I'm not sure if I agree"
answer.maybe,"I'm not sure if I agree"
answer.maybe,"I'm not sure"
answer.maybe,"no idea"
answer.maybe,"not sure"
answer.maybe,"not sure"
answer.maybe,dunno
answer.maybe,maybe
answer.maybe,maybe
answer.maybe,meh
answer.maybe,perhaps
answer.maybe,possibly
answer.maybe,unsure
answer.maybe,wait
answer.no,"I changed my mind"
answer.no,"I don't agree"
answer.no,"I don't agree"
answer.no,"I don't agree"
answer.no,"I don't agree"
answer.no,"I don't agree"
answer.no,"absolutely not"
answer.no,"belay that"
answer.no,"cut it out"
answer.no,"don't do anything"
answer.no,"don't do it"
answer.no,"forget it"
answer.no,"never mind"
answer.no,"no thanks"
answer.no,"no way"
answer.no,"on second thought, don't do it"
answer.no,"please don't"
answer.no,"scratch that"
answer.no,cancel
answer.no,denied
answer.no,disengage
answer.no,don't
answer.no,end
answer.no,exit
answer.no,halt
answer.no,n
answer.no,nah
answer.no,nay
answer.no,nay
answer.no,neg
answer.no,negative
answer.no,negatory
answer.no,nein
answer.no,nevermind
answer.no,nevermind
answer.no,no
answer.no,no
answer.no,no
answer.no,nope
answer.no,nope
answer.no,nyet
answer.no,quit
answer.no,skip
answer.no,stop
answer.no,stop
answer.yes,"I agree"
answer.yes,"I agree"
answer.yes,"I agree"
answer.yes,"I agree"
answer.yes,"I agree"
answer.yes,"I agree"
answer.yes,"I agree"
answer.yes,"I agree"
answer.yes,"I agree"
answer.yes,"I'm sure"
answer.yes,"I'm sure"
answer.yes,"I'm sure"
answer.yes,"I'm sure"
answer.yes,"I'm sure"
answer.yes,"aye aye"
answer.yes,"carry on"
answer.yes,"do it"
answer.yes,"do it"
answer.yes,"do it"
answer.yes,"get on with it then"
answer.yes,"go ahead"
answer.yes,"i agree"
answer.yes,"make it happen"
answer.yes,"make it so"
answer.yes,"most assuredly"
answer.yes,"perfect, thanks"
answer.yes,"please do"
answer.yes,"rock on"
answer.yes,"that's correct"
answer.yes,"that's right"
answer.yes,"uh huh"
answer.yes,"yeah, do it"
answer.yes,"yes, do it"
answer.yes,"yes, please"
answer.yes,"you got it"
answer.yes,absolutely
answer.yes,absolutely
answer.yes,absolutely
answer.yes,affirmative
answer.yes,alright
answer.yes,assuredly
answer.yes,aye
answer.yes,certainly
answer.yes,confirmed
answer.yes,continue
answer.yes,continue
answer.yes,correct
answer.yes,da
answer.yes,good
answer.yes,hooray
answer.yes,ja
answer.yes,ok
answer.yes,ok
answer.yes,ok
answer.yes,okay
answer.yes,proceed
answer.yes,righto
answer.yes,sure
answer.yes,sure
answer.yes,sure
answer.yes,sure
answer.yes,sure
answer.yes,sure
answer.yes,sure
answer.yes,sure
answer.yes,sure
answer.yes,thanks
answer.yes,totally
answer.yes,true
answer.yes,y
answer.yes,ya
answer.yes,ya
answer.yes,yay
answer.yes,yea
answer.yes,yeah
answer.yes,yeah
answer.yes,yep
answer.yes,yeppers
answer.yes,yes
answer.yes,yes
```

Paste the examples into the textbox on the import popup:

 

Click the **Import** button to train the classifier.

**Why are there so many duplicate examples in the training data?**  
  
Classifiers are statistical. The same token (a word or phrase) may be considered as evidence for multiple classifications based on the training data. The number of occurrences factors into the prediction. You need many training examples to help the classifier make better predictions in the face of ambiguity.  
  
The ideal model shouldn't _overfit_ or _underfit_ the training data. For instance, the presence of the word "sure" shouldn't automatically be classified as **answer.yes**, because there are variations like "not sure" which mean **answer.maybe**. You'll often find that when you have a short phrase (e.g. "i agree", "sure"), the occurrences of those tokens are spread across the possible classifications. If you were to do a _one-vs-all_ comparison, "sure" may occur far less often in **answer.yes** than in the rest of the classifications.  
  
Another thing to consider is that if there are many ways to say the same thing, like "hello", then the significance of any particular way to say it is diminished. The phrase "hi" becomes one of 50 ways to say hello, which are then all considered to be equally likely (each with a 2% chance; 1 in 50). That also affects the comparisons between classifications, where "hi" may be more than 2% probable somewhere else. When saying hello, we know that "hi" is not equally probable with "howdy" or "yo"; someone is much more likely to say "hi".  
  
The primary way to represent that increased likelihood is through frequency in the training data. The classifier would learn that there are 50 ways to say hello, but that "hi" is far more common than most of the alternatives.

# Test predictions

After training with the above steps, you should now see 3 classifications and 152 training examples on the classifier's card:

 

Let's see how well the classifier works.

In the **Train Classifier** section, try a few examples by typing them and pressing ENTER:

- yep
- no thanks
- not sure
- stop
- go ahead

You should see a prediction for each, like:

 

The classifier correctly predicts the intent for each of those examples. However, that's not too impressive since each of those examples is found in the training data.

You can also try something like:

- that's not what I wanted
- that is what I want

The classifier correctly predicts _no_ and _yes_ for those, respectively; even though you won't find _"want"_ mentioned anywhere in the training data.

# Continue training the classifier

This classifier already does a pretty good job, but it doesn't cover everything.

Enter _"k!"_ in **Train Classifier** and press ENTER:

 

We know that _"k!"_ should be considered as a _yes_ response, but we're getting back _maybe_. This is where the machine _learning_ comes in.

Click the **Train** button below the prediction.

In the **Classification:** field, hover over **answer.maybe** and click the **(x)** icon (top right) to delete it. Type the letter _"y"_ instead and select **answer.yes** from the autocomplete menu.

 

Click the **Save Changes** button.

The card will refresh, and you'll now see 153 training examples.

If you type _"k!"_ again you should now get a _yes_ prediction.

You may need to train the same example multiple times in order to properly teach the model how to classify it. This can occur when you have similar responses with different classifications, like _"sure"_ (yes) and _"not sure"_ (maybe).

It's interesting to watch the classifier consider new evidence when making a prediction:

- _"sure"_ is predicted to mean _yes_.
- _"sure, wait"_ is predicted to mean _maybe_.
- _"sure, wait – don't do it, I changed my mind"_ is predicted to mean _no_.

You'll also see that some ambiguity is recognized by the classifier:

- _"I'm not going to say no"_ is predicted to mean _maybe_.

# Next steps

- Use the classifier in a conversational bot behavior.
- Try making a more complex classifier for sentiment analysis or intent detection.

