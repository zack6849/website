package com.zack6849.website.services.storage.exception;

public class StorageException extends RuntimeException {

    public StorageException(String message){
        super(message);
    }

    public StorageException(String message, Throwable cause){
        super(message, cause);
    }

}
