# Secret Message

A secure messaging application that uses asymmetric RSA encryption and private channel websockets to ensure that messages can only be read by the intended recipient. Messages automatically expire after a set time and are securely deleted from the system.

## Getting Started

### Prerequisites

- Docker 20.10.13+ or Docker Desktop 3.4.0+ (Docker with docker compose v2 integrated)
- Git

The project is set up to use Docker for all dependencies, so you don't need to install PHP, Composer, Node.js, or MySQL directly on your system.

### Installation and Setup

```bash 
git clone https://github.com/sandergroenen/secretmessage.git
```
The application can be started with a single command using the start-local script (compatible with Linux and macOS only):

```bash
./start-local.sh
```

This script will:
1. Download Docker images and spin up containers
2. Install PHP dependencies via Composer
3. Install JavaScript dependencies via npm
4. Set up the database and run migrations
5. Seed the database with default users
6. Start the Vite server (asset building), Reverb WebSocket server, and queue workers (Events driver)

Once the script completes, you can access the application at [http://localhost](http://localhost).

## Using the Application

### Default User Accounts

The application comes with two default user accounts for testing:

- **User A**
  - Email: userA@example.com
  - Password: password

- **User B**
  - Email: userB@example.com
  - Password: password

To simulate a conversation between two users, you can log in with one account in your regular browser window and the other account in an incognito/private window.

### Key Management

When you first log in, you'll be prompted to generate a key pair (public and private keys). The system will:

1. Store your public key on the server
2. Display your private key **once** for you to save

**Important**: You must save your private key in a secure location. It is not stored on the server and cannot be recovered if lost. Without your private key, you will not be able to decrypt messages sent to you.

### Sending Messages

To send a message:

1. Navigate to the dashboard
2. Select a recipient from the dropdown
3. Enter your message
4. Set an expiration time
5. Click "Send Message"

The message will be encrypted with the recipient's public key before being stored in the database. And the message ID will be printed, *COPY this for userB (see step 1 in receiving messages)*

### Receiving Messages

To read a message sent to you:

1. You'll need the message ID from the sender (this should be shared outside the system for extra security)
2. Enter the message ID in the "Read Secret Message" section
3. When prompted, paste your private key to decrypt the message
4. The decrypted message will be displayed with a countdown timer showing when it will expire
5. Click "Message read..." when you're done to mark it as read

## Technical Overview

### Tech stack
  * Docker
  * Laravel 12 
  * Php 8.4-fpm
  * nginx latest
  * mysql 8.0

### Security Features

- **Asymmetric RSA Encryption**: Uses phpseclib3 to implement RSA public/private key encryption
- **Message Expiration**: Messages are automatically deleted after their expiration time
- **Private Channel WebSockets**: Channels were decrypted messages are broadcasted are secured by authorization callback authenticating the logged in user as being the recipient
- **No Stored Decryption Keys**: Private keys are never stored on the server

### Key Components

#### Domain-Driven Design

The application follows a domain-driven design approach with a clear separation of concerns:

- **Domain Layer** (`app/Domain/Message/`): Contains all message-related business logic
  - **Dto**: Data Transfer Objects for message data
  - **Events**: Event classes for message lifecycle events
  - **Listeners**: Event listeners for handling message events
  - **Models**: Eloquent models for message data
  - **Repositories**: Repository interfaces and implementations
  - **Services**: Service classes for encryption, key management, etc.
  - **Controllers**: Controllers for handling message-related requests

#### Repository Pattern

The application uses the repository pattern to abstract data access:

- **Repository Interfaces** (`app/Domain/Message/Repositories/Interfaces/`): Define contracts for data access
- **Repository Implementations** (`app/Domain/Message/Repositories/`): Implement the interfaces using Eloquent

This pattern allows for easy switching between different data sources or ORM systems without changing the business logic.

#### Data Transfer Objects (DTOs)

The `MessageDto` class serves as a data structure independent of the underlying data repository. This allows for:

- Clean separation between domain logic and data access
- Consistent data structure throughout the application
- Easy addition of new data sources in the future

#### Real-time Updates with Laravel Reverb WebSockets

The application uses Laravel Reverb for WebSockets communication:

- **Private Channels**: Each user has a private channel for receiving messages
- **User Authorization**: Channels are authorized based on user authentication
- **Real-time Decryption**: Messages are decrypted and displayed in real-time

#### Event-Driven Architecture

The application uses Laravel's event system for handling message lifecycle events:

- **MessageDecryptedAndReceivedEvent**: Triggered when a message is decrypted
- **MessageExpiredEvent**: Triggered when a message expires
- **ExpiredMessageListener**: Handles message expiration by deleting the message

## Running Static analysis and Tests
- To run phpstan use the following command on your host:  
```bash
docker compose exec -u appuser app bash -c 'XDEBUG_MODE=off cd /var/www && vendor/bin/phpstan --memory-limit=2G'
```
- To run tests use the following command on your host: 
```bash
docker compose exec -u appuser app bash -c 'XDEBUG_MODE=off cd /var/www && php artisan test'
```
The XDEBUG_MODE=off prevents xdebug connection error's in the cli.
- The tests are also run on Github actions for CI/CD purposes. for setup see .github/workflows/tests.yml and for runs see  https://github.com/sandergroenen/secretmessage/actions
## License

This project is licensed under the MIT License