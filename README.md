# Delightful Photos

[Delightful Photos][1] is a fork of [Pixelfed][2], with minor adjustments. It
tracks the upstream version, with modifications applied.

## Syncing with Upstream

Add a local remote referencing upstream:

```sh
git remote add upstream https://github.com/pixelfed/pixelfed.git
```

Then:

```sh
git fetch upstream
git rebase v0.12.5
git push -f origin dev
```

## Build the container

The container is built and pushed to [GitHub Packages][3].

Authenticate with [a PAT (classic), with at least `write:packages`][4]:

```sh
echo $GITHUB_TOKEN | docker login ghcr.io -u USERNAME --password-stdin
```

Build and push the version and `latest` tags:

```sh
docker build . -t ghcr.io/delightfulsystems/photos:latest
docker build . -t ghcr.io/delightfulsystems/photos:0.12.5
docker push ghcr.io/delightfulsystems/photos:latest
docker push ghcr.io/delightfulsystems/photos:0.12.5
```

## License

It inherits the upstream license, which follows the AGPL license.

[1]: https://delightful.photos
[2]: https://pixelfed.org
[3]: https://github.com/delightfulsystems/photos/pkgs/container/photos
[4]: https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry
