#!/usr/bin/env bash

if [[ "$#" -lt 1 ]]; then
    echo "Brew failed - invalid $0 call"

    exit 1;
fi

packageName="$1"
packageArgs="$2"

echo "Handling '$packageName' brew package..."

if [[ $(brew ls --versions "$packageName") ]]; then
    if brew outdated "$packageName"; then
        echo "Package upgrade is not required, skipping"
    else
        echo "Updating package...";
        brew upgrade "$packageName"
        if [ $? -ne 0 ]; then
            echo "Upgrade failed"

            exit 1
        fi
    fi
else
    echo "Package not available - installing..."
    brew install "$packageName" $packageArgs
    if [ $? -ne 0 ]; then
        echo "Install failed"

        exit 1
    fi
fi

echo "Linking installed package..."
brew link "$packageName"
