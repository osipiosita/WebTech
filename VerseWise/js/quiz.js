// DOM Elements
let questionNumberElement = document.querySelector('.question-number');
let questionElement = document.querySelector('.question');
let answerButtons = document.querySelectorAll('.answer-button');
let nextButton = document.querySelector('.next-button');
let progressBar = document.querySelector('.progress');

const image = document.querySelector('.jesus-img');

const music = document.getElementById('background-music');
window.addEventListener('load', () => {
    music.play();

})

// Global state
let quizData = [];
let currentQuestionIndex = 0;
let userAnswers = [];
let score = 0;

async function initializeQuiz() {
    try {
        const response = await fetch('../actions/quizData.php');
        
        if (!response.ok) {
            throw new Error(`Network response was not ok: ${response.status}`);
        }

        const data = await response.json();

        // Check if data is valid
        if (!data || data.error) {
            questionElement.textContent = data.error || 'No quiz data found.';
            return;
        }

        // Ensure we have the correct data structure
        const quizQuestions = data.questions || data;

        // Validate quiz data
        if (!quizQuestions || quizQuestions.length === 0) {
            questionElement.textContent = 'No quiz questions available.';
            return;
        }

        // Set global quiz data
        quizData = quizQuestions;

        // Reset quiz state
        currentQuestionIndex = 0;
        userAnswers = [];
        score = 0;

        // Update progress and render first question
        updateProgressBar();
        renderQuestion();
        attachEventListeners();

    } catch (error) {
        console.error('Complete error details:', error);
        
        if (questionElement) {
            questionElement.textContent = 'Error loading quiz. Please try again later.';
        }
    }
}

// Function to update progress bar
function updateProgressBar() {
    const progress = ((currentQuestionIndex + 1) / quizData.length) * 100;
    progressBar.style.width = `${progress}%`;
}

// Function to render the current question
function renderQuestion() {
    // Get the current question
    const currentQuestion = quizData[currentQuestionIndex];

    // Update question number and text
    questionNumberElement.textContent = `Question ${currentQuestionIndex + 1} of ${quizData.length}`;
    questionElement.textContent = currentQuestion.question;

    // Shuffle and populate answers
    const allChoices = [...currentQuestion.choices].sort(() => Math.random() - 0.5);

    // Update answer buttons
    answerButtons.forEach((button, index) => {
        button.textContent = `${String.fromCharCode(65 + index)}. ${allChoices[index]}`;
        button.dataset.answer = allChoices[index];
        button.classList.remove('selected');
        button.style.backgroundColor = ''; // Reset background color
        button.style.transform = ''; // Reset transformation
        button.disabled = false; // Re-enable the butto
    });

    // Disable the Next button
    nextButton.disabled = true;
}

// Function to handle answer selection
answerButtons.forEach(button => {
    button.addEventListener('click', () => {
        // Clear selected state for all buttons
        answerButtons.forEach(btn => btn.classList.remove('selected'));

        // Mark this button as selected
        button.classList.add('selected');

        // Enable the Next button
        nextButton.disabled = false;
    });
});

// Handle Next button click
nextButton.addEventListener('click', () => {
    // Get selected answer
    const selectedButton = document.querySelector('.answer-button.selected');
    const selectedAnswer = selectedButton ? selectedButton.dataset.answer : null;

    // Check correctness and update score
    if (selectedAnswer === quizData[currentQuestionIndex].correct_answer) {
        score++;
        triggerConfetti(); 
        showVisualFeedback(true);
    }
    else{
        showVisualFeedback(false);
    }

    // Save the user's answer
    userAnswers.push({
        question: quizData[currentQuestionIndex].question,
        selectedAnswer,
        correctAnswer: quizData[currentQuestionIndex].correct_answer,
        reference: quizData[currentQuestionIndex].reference,
        isCorrect: selectedAnswer === quizData[currentQuestionIndex].correct_answer,
    });

    // Move to next question or show results
    currentQuestionIndex++;
    updateProgressBar();
    
    if (currentQuestionIndex < quizData.length) {
        renderQuestion();
    } else {
        showResults();
    }
});

function triggerConfetti() {
    confetti({
        particleCount: 100,
        spread: 70,
        origin: { x: 0.5, y: 0.5 }
    });
}

function showVisualFeedback(isCorrect){
    const selectedButton = document.querySelector('.answer-button.selected');

    if (isCorrect) {
        selectedButton.style.backgroundColor = 'green';  // Correct answer
        selectedButton.style.transform = 'scale(1.1)';  // Slight zoom-in effect
    } else {
        // Wrong answer visual feedback
        selectedButton.style.backgroundColor = 'red';  // Wrong answer

        // Show image
        image.style.display = 'block';
        // Add floating effect to image 
        image.style.animation = 'float 4s ease-in-out infinite';
        
        // Hide the image after the animation ends 
        setTimeout(() => {
            image.style.display = 'none';
        }, 2000);  // Matches animation duration
    }

    // Disable answer buttons and enable Next button
    answerButtons.forEach(button => {
        button.disabled = true;
    });

    nextButton.disabled = false;
}

// Function to show results
function showResults() {
    const quizContainer = document.querySelector('.quiz-container');
    let resultsHTML = `
        <div class="results">
            <h2>Quiz Completed!</h2>
            <div class="score">Your Score: ${score} out of ${quizData.length}</div>
            <div class="percentage">Percentage: ${((score / quizData.length) * 100).toFixed(1)}%</div>
            
            <div class="answers-review">
                <h3>Review Your Answers:</h3>
    `;

    console.log('Final Score:', score);

    userAnswers.forEach((answer, index) => {
        resultsHTML += `
            <div class="answer-review ${answer.isCorrect ? 'correct' : 'incorrect'}">
                <p class="question">${index + 1}. ${answer.question}</p>
                <p>Your answer: ${answer.selectedAnswer}</p>
                <p>Correct answer: ${answer.correctAnswer}</p>
                <p class="reference">Reference: ${answer.reference}</p>
            </div>
        `;
    });
    quizContainer.innerHTML = resultsHTML;


     fetch('../actions/saveQuizResults.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            score,
            totalQuestions: quizData.length
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Quiz results saved successfully.');
        } else {
            console.error('Failed to save quiz results.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });

 



}

// Function to attach event listeners
function attachEventListeners() {
    answerButtons.forEach(button => {
        button.addEventListener('click', () => {
            answerButtons.forEach(btn => btn.classList.remove('selected'));
            button.classList.add('selected');
            nextButton.disabled = false;
        });
    });
}

// Start the quiz
initializeQuiz();