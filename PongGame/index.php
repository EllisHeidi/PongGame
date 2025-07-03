<?php
// Pong Game - PHP Version for InfinityFree
// This file serves the complete game as HTML/CSS/JavaScript

// Set content type
header('Content-Type: text/html; charset=UTF-8');

// Game configuration
$gameTitle = "Pong Game";
$maxScore = 10;
$canvasWidth = 900;
$canvasHeight = 500;

// Optional: Add basic visitor tracking
$visitorFile = 'visitors.txt';
if (file_exists($visitorFile)) {
    $visits = (int)file_get_contents($visitorFile);
    $visits++;
} else {
    $visits = 1;
}
file_put_contents($visitorFile, $visits);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($gameTitle); ?></title>
    <meta name="description" content="Play the classic Pong game online ">
    <meta name="keywords" content="pong, game, online, classic, arcade, retro">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap');
        
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Orbitron', monospace;
            color: #00ffff;
            overflow: hidden;
        }
        
        .game-container {
            text-align: center;
            position: relative;
            padding: 20px;
            border-radius: 20px;
            background: rgba(0, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 255, 255, 0.2);
            box-shadow: 0 0 50px rgba(0, 255, 255, 0.3);
        }
        
        canvas {
            border: 2px solid #00ffff;
            background: radial-gradient(circle at center, #001122 0%, #000011 100%);
            border-radius: 10px;
            box-shadow: 
                0 0 30px rgba(0, 255, 255, 0.5),
                inset 0 0 30px rgba(0, 255, 255, 0.1);
        }
        
        .score {
            font-size: 32px;
            font-weight: 900;
            margin: 15px 0;
            text-shadow: 0 0 20px #00ffff;
            letter-spacing: 2px;
        }
        
        .score span {
            color: #ff6b6b;
            text-shadow: 0 0 20px #ff6b6b;
        }
        
        .controls {
            margin-top: 20px;
            font-size: 14px;
            opacity: 0.8;
            display: flex;
            justify-content: space-between;
            max-width: 400px;
            margin: 20px auto 0;
        }
        
        .control-group {
            padding: 10px;
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(0, 255, 255, 0.1);
        }
        
        .instructions {
            margin-top: 15px;
            font-size: 14px;
            opacity: 0.9;
            font-weight: 700;
            text-shadow: 0 0 10px #00ffff;
            animation: pulse 2s ease-in-out infinite alternate;
        }
        
        @keyframes pulse {
            from { opacity: 0.7; }
            to { opacity: 1; }
        }
        
        .fps-counter {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 12px;
            opacity: 0.6;
        }
        
        .sound-toggle {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 255, 255, 0.2);
            border: 1px solid #00ffff;
            color: #00ffff;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Orbitron', monospace;
            font-size: 12px;
        }
        
        .sound-toggle:hover {
            background: rgba(0, 255, 255, 0.3);
        }
        
        .visit-counter {
            position: absolute;
            bottom: 10px;
            left: 10px;
            font-size: 12px;
            opacity: 0.6;
        }
        
        .footer {
            margin-top: 20px;
            font-size: 12px;
            opacity: 0.7;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="game-container">
        <div class="fps-counter" id="fpsCounter">FPS: 60</div>
        <button class="sound-toggle" id="soundToggle">🔊 Sound ON</button>
        <div class="visit-counter">Visits: <?php echo number_format($visits); ?></div>
        
        <div class="score">
            <span id="player1Score">0</span> : <span id="player2Score">0</span>
        </div>
        
        <canvas id="gameCanvas" width="<?php echo $canvasWidth; ?>" height="<?php echo $canvasHeight; ?>"></canvas>
        
        <div class="controls">
            <div class="control-group">
                <div><strong>Player 1</strong></div>
                <div>W ↑ | S ↓</div>
            </div>
            <div class="control-group">
                <div><strong>Player 2</strong></div>
                <div>↑ Up | ↓ Down</div>
            </div>
        </div>
        
        <div class="instructions">
            Press SPACE to start • First to <?php echo $maxScore; ?> points wins!
        </div>
        
        <div class="footer">
            <?php echo date('Y'); ?> Enhanced Pong Game | Hosted on InfinityFree
        </div>
    </div>

    <script>
        // Game configuration from PHP
        const MAX_SCORE = <?php echo $maxScore; ?>;
        const CANVAS_WIDTH = <?php echo $canvasWidth; ?>;
        const CANVAS_HEIGHT = <?php echo $canvasHeight; ?>;
        
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        const player1ScoreEl = document.getElementById('player1Score');
        const player2ScoreEl = document.getElementById('player2Score');
        const fpsCounterEl = document.getElementById('fpsCounter');
        const soundToggleEl = document.getElementById('soundToggle');

        // Game state
        let gameRunning = false;
        let gameWon = false;
        let winner = '';
        let soundEnabled = true;
        let lastTime = 0;
        let fps = 0;
        let frameCount = 0;

        // Particle system
        const particles = [];
        const trails = [];

        // Enhanced ball physics
        const ball = {
            x: canvas.width / 2,
            y: canvas.height / 2,
            dx: 0,
            dy: 0,
            radius: 10,
            baseSpeed: 6,
            maxSpeed: 12,
            acceleration: 1.05,
            trail: [],
            glowRadius: 20
        };

        // Enhanced paddles
        const paddle1 = {
            x: 20,
            y: canvas.height / 2 - 60,
            width: 12,
            height: 120,
            speed: 8,
            targetY: canvas.height / 2 - 60,
            smoothing: 0.15
        };

        const paddle2 = {
            x: canvas.width - 32,
            y: canvas.height / 2 - 60,
            width: 12,
            height: 120,
            speed: 8,
            targetY: canvas.height / 2 - 60,
            smoothing: 0.15
        };

        const score = {
            player1: 0,
            player2: 0
        };

        // Input handling
        const keys = {};
        
        document.addEventListener('keydown', (e) => {
            keys[e.key.toLowerCase()] = true;
            
            if (e.key === ' ') {
                e.preventDefault();
                if (!gameRunning || gameWon) {
                    startGame();
                }
            }
        });

        document.addEventListener('keyup', (e) => {
            keys[e.key.toLowerCase()] = false;
        });

        soundToggleEl.addEventListener('click', () => {
            soundEnabled = !soundEnabled;
            soundToggleEl.textContent = soundEnabled ? '🔊 Sound ON' : '🔇 Sound OFF';
        });

        // Sound effects (using Web Audio API)
        function playSound(frequency, duration, type = 'sine') {
            if (!soundEnabled) return;
            
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = frequency;
                oscillator.type = type;
                
                gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + duration);
            } catch (e) {
                console.log('Audio not supported');
            }
        }

        function createParticles(x, y, color, count = 8) {
            for (let i = 0; i < count; i++) {
                particles.push({
                    x,
                    y,
                    dx: (Math.random() - 0.5) * 8,
                    dy: (Math.random() - 0.5) * 8,
                    life: 1,
                    decay: 0.02,
                    color,
                    size: Math.random() * 3 + 1
                });
            }
        }

        function startGame() {
            gameRunning = true;
            gameWon = false;
            winner = '';
            
            // Reset scores
            score.player1 = 0;
            score.player2 = 0;
            player1ScoreEl.textContent = score.player1;
            player2ScoreEl.textContent = score.player2;
            
            // Reset ball with random direction
            ball.x = canvas.width / 2;
            ball.y = canvas.height / 2;
            const angle = (Math.random() - 0.5) * Math.PI / 3; // ±30 degrees
            const direction = Math.random() > 0.5 ? 1 : -1;
            ball.dx = Math.cos(angle) * ball.baseSpeed * direction;
            ball.dy = Math.sin(angle) * ball.baseSpeed;
            ball.trail = [];
            
            // Reset paddles
            paddle1.y = canvas.height / 2 - 60;
            paddle2.y = canvas.height / 2 - 60;
            paddle1.targetY = paddle1.y;
            paddle2.targetY = paddle2.y;
            
            particles.length = 0;
        }

        function updatePaddles() {
            // Player 1 controls + smooth movement
            if (keys['w'] && paddle1.targetY > 0) {
                paddle1.targetY -= paddle1.speed;
            }
            if (keys['s'] && paddle1.targetY < canvas.height - paddle1.height) {
                paddle1.targetY += paddle1.speed;
            }
            
            // Player 2 controls + smooth movement
            if (keys['arrowup'] && paddle2.targetY > 0) {
                paddle2.targetY -= paddle2.speed;
            }
            if (keys['arrowdown'] && paddle2.targetY < canvas.height - paddle2.height) {
                paddle2.targetY += paddle2.speed;
            }
            
            // Smooth paddle movement
            paddle1.y += (paddle1.targetY - paddle1.y) * paddle1.smoothing;
            paddle2.y += (paddle2.targetY - paddle2.y) * paddle2.smoothing;
        }

        function updateBall() {
            if (!gameRunning || gameWon) return;

            // Add to trail
            ball.trail.push({ x: ball.x, y: ball.y });
            if (ball.trail.length > 15) {
                ball.trail.shift();
            }

            ball.x += ball.dx;
            ball.y += ball.dy;

            // Ball collision with top and bottom walls
            if (ball.y <= ball.radius || ball.y >= canvas.height - ball.radius) {
                ball.dy = -ball.dy;
                ball.y = Math.max(ball.radius, Math.min(canvas.height - ball.radius, ball.y));
                playSound(400, 0.1, 'square');
                createParticles(ball.x, ball.y, '#00ffff', 6);
            }

            // Enhanced paddle collision detection
            function checkPaddleCollision(paddle, isLeft) {
                const ballLeft = ball.x - ball.radius;
                const ballRight = ball.x + ball.radius;
                const ballTop = ball.y - ball.radius;
                const ballBottom = ball.y + ball.radius;
                
                const paddleLeft = paddle.x;
                const paddleRight = paddle.x + paddle.width;
                const paddleTop = paddle.y;
                const paddleBottom = paddle.y + paddle.height;
                
                if (ballBottom > paddleTop && ballTop < paddleBottom) {
                    if (isLeft && ballLeft <= paddleRight && ball.dx < 0) {
                        return true;
                    } else if (!isLeft && ballRight >= paddleLeft && ball.dx > 0) {
                        return true;
                    }
                }
                return false;
            }

            // Ball collision with paddles
            if (checkPaddleCollision(paddle1, true)) {
                ball.dx = Math.abs(ball.dx) * ball.acceleration;
                const hitPos = (ball.y - paddle1.y) / paddle1.height;
                ball.dy = (hitPos - 0.5) * 12;
                ball.x = paddle1.x + paddle1.width + ball.radius;
                
                // Limit max speed
                const speed = Math.sqrt(ball.dx * ball.dx + ball.dy * ball.dy);
                if (speed > ball.maxSpeed) {
                    ball.dx = (ball.dx / speed) * ball.maxSpeed;
                    ball.dy = (ball.dy / speed) * ball.maxSpeed;
                }
                
                playSound(300, 0.1, 'sawtooth');
                createParticles(ball.x, ball.y, '#ff6b6b', 10);
            }

            if (checkPaddleCollision(paddle2, false)) {
                ball.dx = -Math.abs(ball.dx) * ball.acceleration;
                const hitPos = (ball.y - paddle2.y) / paddle2.height;
                ball.dy = (hitPos - 0.5) * 12;
                ball.x = paddle2.x - ball.radius;
                
                // Limit max speed
                const speed = Math.sqrt(ball.dx * ball.dx + ball.dy * ball.dy);
                if (speed > ball.maxSpeed) {
                    ball.dx = (ball.dx / speed) * ball.maxSpeed;
                    ball.dy = (ball.dy / speed) * ball.maxSpeed;
                }
                
                playSound(300, 0.1, 'sawtooth');
                createParticles(ball.x, ball.y, '#ff6b6b', 10);
            }

            // Scoring - only score once per ball
            if (ball.x < -ball.radius && ball.dx < 0) {
                score.player2++;
                player2ScoreEl.textContent = score.player2;
                playSound(200, 0.3, 'triangle');
                createParticles(50, canvas.height / 2, '#00ff00', 15);
                ball.dx = 0; // Stop ball movement to prevent multiple scoring
                ball.dy = 0;
                resetBall();
            } else if (ball.x > canvas.width + ball.radius && ball.dx > 0) {
                score.player1++;
                player1ScoreEl.textContent = score.player1;
                playSound(200, 0.3, 'triangle');
                createParticles(canvas.width - 50, canvas.height / 2, '#00ff00', 15);
                ball.dx = 0; // Stop ball movement to prevent multiple scoring
                ball.dy = 0;
                resetBall();
            }

            // Check for winner
            if (score.player1 >= MAX_SCORE) {
                gameWon = true;
                winner = 'Player 1';
                gameRunning = false;
                playSound(500, 1, 'sine');
            } else if (score.player2 >= MAX_SCORE) {
                gameWon = true;
                winner = 'Player 2';
                gameRunning = false;
                playSound(500, 1, 'sine');
            }
        }

        function resetBall() {
            setTimeout(() => {
                ball.x = canvas.width / 2;
                ball.y = canvas.height / 2;
                const angle = (Math.random() - 0.5) * Math.PI / 3;
                const direction = Math.random() > 0.5 ? 1 : -1;
                ball.dx = Math.cos(angle) * ball.baseSpeed * direction;
                ball.dy = Math.sin(angle) * ball.baseSpeed;
                ball.trail = [];
            }, 1000);
        }

        function updateParticles() {
            for (let i = particles.length - 1; i >= 0; i--) {
                const p = particles[i];
                p.x += p.dx;
                p.y += p.dy;
                p.life -= p.decay;
                p.dx *= 0.98;
                p.dy *= 0.98;
                
                if (p.life <= 0) {
                    particles.splice(i, 1);
                }
            }
        }

        function draw() {
            // Clear canvas with fade effect
            ctx.fillStyle = 'rgba(0, 1, 17, 0.1)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Draw center line with glow
            ctx.strokeStyle = '#00ffff';
            ctx.lineWidth = 2;
            ctx.shadowColor = '#00ffff';
            ctx.shadowBlur = 10;
            ctx.setLineDash([10, 10]);
            ctx.beginPath();
            ctx.moveTo(canvas.width / 2, 0);
            ctx.lineTo(canvas.width / 2, canvas.height);
            ctx.stroke();
            ctx.setLineDash([]);
            ctx.shadowBlur = 0;

            ctx.fillStyle = '#00ffff';
            ctx.shadowColor = '#00ffff';
            ctx.shadowBlur = 15;
            
            // Add rounded corners to paddles
            ctx.beginPath();
            ctx.roundRect(paddle1.x, paddle1.y, paddle1.width, paddle1.height, 6);
            ctx.fill();
            
            ctx.beginPath();
            ctx.roundRect(paddle2.x, paddle2.y, paddle2.width, paddle2.height, 6);
            ctx.fill();

            // Draw ball trail
            ctx.shadowBlur = 0;
            for (let i = 0; i < ball.trail.length; i++) {
                const alpha = (i / ball.trail.length) * 0.5;
                const size = (i / ball.trail.length) * ball.radius;
                ctx.fillStyle = `rgba(0, 255, 255, ${alpha})`;
                ctx.beginPath();
                ctx.arc(ball.trail[i].x, ball.trail[i].y, size, 0, Math.PI * 2);
                ctx.fill();
            }

            // Draw ball with enhanced glow
            const gradient = ctx.createRadialGradient(ball.x, ball.y, 0, ball.x, ball.y, ball.glowRadius);
            gradient.addColorStop(0, '#ffffff');
            gradient.addColorStop(0.3, '#00ffff');
            gradient.addColorStop(1, 'rgba(0, 255, 255, 0)');
            
            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc(ball.x, ball.y, ball.glowRadius, 0, Math.PI * 2);
            ctx.fill();
            
            ctx.fillStyle = '#ffffff';
            ctx.beginPath();
            ctx.arc(ball.x, ball.y, ball.radius, 0, Math.PI * 2);
            ctx.fill();

            // Draw particles
            particles.forEach(p => {
                ctx.fillStyle = `rgba(${p.color === '#00ffff' ? '0, 255, 255' : p.color === '#ff6b6b' ? '255, 107, 107' : '0, 255, 0'}, ${p.life})`;
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                ctx.fill();
            });

            // Draw game messages
            ctx.font = '32px Orbitron';
            ctx.textAlign = 'center';
            ctx.fillStyle = '#00ffff';
            ctx.shadowColor = '#00ffff';
            ctx.shadowBlur = 20;
            
            if (!gameRunning && !gameWon) {
                ctx.fillText('Press SPACE to Start', canvas.width / 2, canvas.height / 2 + 50);
            } else if (gameWon) {
                ctx.fillText(`${winner} Wins!`, canvas.width / 2, canvas.height / 2 - 30);
                ctx.font = '20px Orbitron';
                ctx.fillText('Press SPACE to Play Again', canvas.width / 2, canvas.height / 2 + 20);
            }
            
            ctx.shadowBlur = 0;
        }

        function gameLoop(currentTime) {
            // Calculate FPS
            if (currentTime - lastTime >= 1000) {
                fps = frameCount;
                frameCount = 0;
                lastTime = currentTime;
                fpsCounterEl.textContent = `FPS: ${fps}`;
            }
            frameCount++;

            updatePaddles();
            updateBall();
            updateParticles();
            draw();
            requestAnimationFrame(gameLoop);
        }

        // Start the game loop
        requestAnimationFrame(gameLoop);
    </script>
</body>
</html>
