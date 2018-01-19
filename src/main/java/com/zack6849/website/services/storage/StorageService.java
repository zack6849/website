package com.zack6849.website.services.storage;

import org.springframework.core.io.Resource;
import org.springframework.web.multipart.MultipartFile;

import java.nio.file.Path;
import java.util.stream.Stream;

public interface StorageService {
    void init();

    Path store(MultipartFile file);
    Path store(MultipartFile file, String path);
    Path store(MultipartFile file, Path path);

    Path load(String filename);
    Path load(String filename, String path);
    Path load(String filename, Path path);

    Stream<Path> loadAll();
    Stream<Path> loadAll(String path);
    Stream<Path> loadAll(Path path);

    Resource loadAsResource(String filename);
    Resource loadAsResource(String filename, String path);
    Resource loadAsResource(String filename, Path path);

    StorageProperties getProperties();
}
